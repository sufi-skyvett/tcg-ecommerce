<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardDuelma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CardController extends Controller
{
    public function index(Request $request)
    {
        $q        = $request->string('q')->toString();
        $game     = $request->string('game')->toString();
        $active   = $request->input('active');      // null|0|1
        $sealed   = $request->input('sealed');      // null|0|1
        $trashed  = $request->boolean('trashed');   // show soft deleted

        $cards = Card::query()
            ->when($trashed, fn($qq) => $qq->withTrashed())
            ->when($q !== '', fn($qq) => $qq->search($q))
            ->when($game !== '', fn($qq) => $qq->game($game))
            ->when($active !== null && $active !== '', fn($qq) => $qq->where('is_active', (bool)$active))
            ->when($sealed !== null && $sealed !== '', fn($qq) => $qq->where('is_sealed_product', (bool)$sealed))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('cards.index', compact('cards', 'q', 'game', 'active', 'sealed', 'trashed'));
    }

    public function create()
    {
        $card = new Card([
            'is_active' => true,
            'is_sealed_product' => false,
        ]);

        return view('cards.create', compact('card'));
    }

    public function store(Request $request)
    {
        $data = $this->validatedData($request);

        // Handle images
        $data = $this->handleImages($request, $data);

        $card = Card::create($data);

        return redirect()
            ->route('cards.show', $card)
            ->with('success', 'Card created.');
    }

    public function show(Card $card)
    {
        return view('cards.show', compact('card'));
    }

    public function edit(Card $card)
    {
        return view('cards.edit', compact('card'));
    }

    public function update(Request $request, Card $card)
    {
        $data = $this->validatedData($request, $card->id);

        $data = $this->handleImages($request, $data, $card);

        $card->update($data);

        return redirect()
            ->route('cards.show', $card)
            ->with('success', 'Card updated.');
    }

    public function destroy(Card $card)
    {
        $card->delete();

        return redirect()
            ->route('cards.index')
            ->with('success', 'Card deleted (soft).');
    }

    public function restore(Card $card)
    {
        // Route-model binding for trashed requires withTrashed in route binding.
        // Easiest: use Card::withTrashed()->findOrFail($id) instead of Card $card if needed.
        if (method_exists($card, 'restore')) {
            $card->restore();
        }

        return redirect()
            ->route('cards.show', $card)
            ->with('success', 'Card restored.');
    }

    /**
     * Centralized validation for store/update
     */
    private function validatedData(Request $request, ?int $ignoreId = null): array
    {
        return $request->validate([
            'name'             => ['required', 'string', 'max:255'],
            'card_code'        => [
                'required',
                'string',
                'max:50',
                Rule::unique('cards', 'card_code')->ignore($ignoreId),
            ],
            'barcode'          => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string'],

            // Images (store path to front_image_path / back_image_path)
            'front_image'      => ['nullable', 'image', 'max:4096'],
            'back_image'       => ['nullable', 'image', 'max:4096'],
            'remove_front'     => ['nullable', 'boolean'],
            'remove_back'      => ['nullable', 'boolean'],

            'game'             => ['nullable', 'string', 'max:50'],
            'brand'            => ['nullable', 'string', 'max:50'],
            'language'         => ['nullable', 'string', 'max:30'],
            'rarity'           => ['nullable', 'string', 'max:50'],
            'finish'           => ['nullable', 'string', 'max:50'],

            'set_id'           => ['nullable', 'integer'], // adjust if you want exists:sets,id
            'collector_number' => ['nullable', 'string', 'max:50'],
            'release_date'     => ['nullable', 'date'],

            'is_active'        => ['sometimes', 'boolean'],
            'is_sealed_product'=> ['sometimes', 'boolean'],
        ], [
            'front_image.image' => 'Front image must be a valid image file.',
            'back_image.image'  => 'Back image must be a valid image file.',
        ]);
    }

    /**
     * Save/remove images and map to model fields.
     */
    private function handleImages(Request $request, array $data, ?Card $existing = null): array
    {
        // Remove front
        if ($request->boolean('remove_front')) {
            if ($existing?->front_image_path) {
                Storage::disk('public')->delete($existing->front_image_path);
            }
            $data['front_image_path'] = null;
        }

        // Remove back
        if ($request->boolean('remove_back')) {
            if ($existing?->back_image_path) {
                Storage::disk('public')->delete($existing->back_image_path);
            }
            $data['back_image_path'] = null;
        }

        // Upload front
        if ($request->hasFile('front_image')) {
            if ($existing?->front_image_path) {
                Storage::disk('public')->delete($existing->front_image_path);
            }
            $data['front_image_path'] = $request->file('front_image')->store('cards/front', 'public');
        }

        // Upload back
        if ($request->hasFile('back_image')) {
            if ($existing?->back_image_path) {
                Storage::disk('public')->delete($existing->back_image_path);
            }
            $data['back_image_path'] = $request->file('back_image')->store('cards/back', 'public');
        }

        // Cleanup: these are form-only keys, not DB columns
        unset($data['front_image'], $data['back_image'], $data['remove_front'], $data['remove_back']);

        // Ensure booleans always set (checkbox behavior)
        $data['is_active'] = $request->boolean('is_active');
        $data['is_sealed_product'] = $request->boolean('is_sealed_product');

        return $data;
    }

    public function fetchFromFandom(Request $request)
    {
        $request->validate([
            'set_code' => 'required|string|max:50',
            'collector_number' => 'required|string|max:50',
        ]);

        $rawSet = trim($request->input('set_code'));
        $rawNum = trim($request->input('collector_number'));

        // Normalize set code (e.g., "24EX1" -> "DM24-EX1")
        $normalizedSet = Str::startsWith(strtoupper($rawSet), 'DM') ? strtoupper($rawSet) : 'DM' . strtoupper($rawSet);

        // 1. Check local DB first
        $card = CardDuelma::where(function ($q) use ($rawSet, $normalizedSet) {
            $q->where('set_code', $rawSet)
              ->orWhere('set_code', $normalizedSet);
        })->where(function ($q) use ($rawNum) {
            $q->where('collector_number', $rawNum)
              ->orWhere('collector_number', 'like', "{$rawNum}/%");
        })->first();

        if ($card) {
            return response()->json(['success' => true, 'card' => $card]);
        }

        // 2. Search Fandom API
        $endpoint = 'https://duelmasters.fandom.com/api.php';
        $searchTerms = sprintf('"%s" "%s"', $rawSet, $rawNum);

        $searchResponse = Http::withHeaders([
            'User-Agent' => 'MDS_OCG_Community_Tool/1.0 (contact@mds.local)',
        ])->get($endpoint, [
            'action' => 'query',
            'list' => 'search',
            'srsearch' => $searchTerms,
            'format' => 'json',
        ]);

        $searchResults = $searchResponse->json('query.search', []);

        if (empty($searchResults)) {
            return response()->json([
                'success' => false,
                'message' => "No card found for {$rawSet} #{$rawNum} on Duel Masters Fandom Wiki.",
            ], 404);
        }

        $pageTitle = $searchResults[0]['title'];

        // 3. Get page content and image list
        $pageResponse = Http::withHeaders([
            'User-Agent' => 'MDS_OCG_Community_Tool/1.0 (contact@mds.local)',
        ])->get($endpoint, [
            'action' => 'query',
            'titles' => $pageTitle,
            'prop' => 'revisions|images',
            'rvprop' => 'content',
            'format' => 'json',
        ]);

        $pages = $pageResponse->json('query.pages', []);
        $pageData = reset($pages);
        $wikitext = $pageData['revisions'][0]['*'] ?? '';

        $extract = function ($field) use ($wikitext) {
            if (preg_match('/\|\s*' . preg_quote($field, '/') . '\s*=\s*(.+)/i', $wikitext, $m)) {
                return trim(explode("\n", $m[1])[0]);
            }
            return null;
        };

        // 4. Download and compress a single low-res image locally
        $localImagePath = null;

        if (!empty($pageData['images'])) {
            foreach ($pageData['images'] as $img) {
                if (preg_match('/\.(png|jpg|jpeg)$/i', $img['title'])) {
                    $imgInfoRes = Http::withHeaders([
                        'User-Agent' => 'MDS_OCG_Community_Tool/1.0 (contact@mds.local)',
                    ])->get($endpoint, [
                        'action' => 'query',
                        'titles' => $img['title'],
                        'prop' => 'imageinfo',
                        'iiprop' => 'url',
                        'format' => 'json',
                    ]);

                    $filePages = $imgInfoRes->json('query.pages', []);
                    $firstFile = reset($filePages);
                    $remoteImageUrl = $firstFile['imageinfo'][0]['url'] ?? null;

                    if ($remoteImageUrl) {
                        $imgContents = Http::withHeaders([
                            'User-Agent' => 'MDS_OCG_Community_Tool/1.0 (contact@mds.local)',
                        ])->get($remoteImageUrl)->body();

                        if ($imgContents) {
                            // Resize to low resolution (~300px width) using native PHP GD
                            $srcImage = @imagecreatefromstring($imgContents);
                            if ($srcImage) {
                                $origW = imagesx($srcImage);
                                $origH = imagesy($srcImage);

                                $targetW = 320; // Lightweight preview size
                                $targetH = (int) ($origH * ($targetW / $origW));

                                $resized = imagecreatetruecolor($targetW, $targetH);
                                imagecopyresampled($resized, $srcImage, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

                                // Save low-res JPEG at 60% quality (~20-40 KB)
                                $safeFileName = 'duel_masters/' . Str::slug($normalizedSet . '-' . str_replace('/', '-', $rawNum)) . '.jpg';
                                ob_start();
                                imagejpeg($resized, null, 60);
                                $compressedData = ob_get_clean();

                                imagedestroy($srcImage);
                                imagedestroy($resized);

                                Storage::disk('public')->put($safeFileName, $compressedData);
                                $localImagePath = $safeFileName;
                            }
                        }
                        break; // Grab only 1 card artwork
                    }
                }
            }
        }

        // 5. Store record in database
        $newCard = CardDuelma::create([
            'name' => $pageTitle,
            'set_code' => $normalizedSet,
            'collector_number' => $rawNum,
            'card_type' => $extract('type') ?? 'Creature',
            'civilization' => $extract('civilization') ?? $extract('civ'),
            'mana_cost' => $extract('cost'),
            'races' => $extract('race'),
            'effect_text' => $extract('english') ?? $extract('effect'),
            'image_path' => $localImagePath,
        ]);

        return response()->json([
            'success' => true,
            'card' => $newCard,
        ]);
    }
}
