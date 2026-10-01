<?php

namespace App\Http\Controllers;

use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CardImportController extends Controller
{
    public function create()
    {

        // Simple form view (optional) - you can replace with your own blade/UI
        return view('cards.import-yugipedia');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'prefix'   => ['required', 'string', 'max:32', 'regex:/^[A-Z0-9]+-[A-Z]{2}$/'], // e.g. DBJH-JP, DBJH-AE
            'start'    => ['required', 'integer', 'min:1', 'max:999'],
            'end'      => ['required', 'integer', 'min:1', 'max:999', 'gte:start'],
            // optional defaults for Card model
            'game'     => ['nullable', 'string', 'max:50'],
            'brand'    => ['nullable', 'string', 'max:50'],
            'language' => ['nullable', 'string', 'max:30'],
        ]);

        $prefix   = strtoupper($validated['prefix']);
        $start    = (int) $validated['start'];
        $end      = (int) $validated['end'];

        // Build full codes like DBJH-JP001
        $codes = [];
        for ($i = $start; $i <= $end; $i++) {
            $codes[] = sprintf('%s%03d', $prefix, $i);
        }

        // Fetch redirect mappings in batches (keep it small-ish)
        $batchSize = 25;

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors  = [];

        foreach (array_chunk($codes, $batchSize) as $chunk) {
            $map = $this->fetchRedirectMap($chunk);

            // For each requested code, create/update if we found a mapping
            foreach ($chunk as $code) {
                if (!isset($map[$code])) {
                    $skipped++;
                    continue;
                }

                $cardName = $map[$code];

                // Defaults you can adjust:
                $defaults = [
                    'name'      => $cardName,
                    'card_code' => $code,

                    // Reasonable defaults for your shop:
                    'game'      => $validated['game']     ?? 'Yu-Gi-Oh!',
                    'brand'     => $validated['brand']    ?? 'Konami',
                    'language'  => $validated['language'] ?? $this->guessLanguageFromPrefix($prefix),

                    'is_active' => true,
                ];

                // If exists -> updated, else -> created
                $existing = Card::withTrashed()->where('card_code', $code)->first();

                if ($existing) {
                    // If it was soft-deleted, restore it
                    if ($existing->trashed()) {
                        $existing->restore();
                    }

                    $existing->fill($defaults)->save();
                    $updated++;
                } else {
                    Card::create($defaults);
                    $created++;
                }
            }

            // polite throttle (optional)
            usleep(250000); // 0.25s
        }

        return back()->with('status', [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'errors'  => $errors,
        ]);
    }

    /**
     * Uses Yugipedia redirects API:
     * query.redirects[].from => code
     * query.redirects[].to   => card name
     */
    private function fetchRedirectMap(array $titles): array
    {
        // IMPORTANT: Use a real User-Agent (some sites may block generic ones)
        $response = Http::withOptions([
                'verify' => false, // dev only
            ])
            ->timeout(20)
            ->get('https://yugipedia.com/api.php', [
                'action'    => 'query',
                'redirects' => 1,
                'format'    => 'json',
                'titles'    => implode('|', $titles),
            ]);

        if (!$response->ok()) {
            return [];
        }

        $json = $response->json();

        $map = [];
        foreach (data_get($json, 'query.redirects', []) as $r) {
            if (!empty($r['from']) && !empty($r['to'])) {
                $map[$r['from']] = $r['to'];
            }
        }

        return $map;
    }

    private function guessLanguageFromPrefix(string $prefix): string
    {
        // You can tweak to match how you store languages in your DB
        if (Str::endsWith($prefix, '-JP')) return 'Japanese';
        if (Str::endsWith($prefix, '-AE')) return 'English (Asia)';
        if (Str::endsWith($prefix, '-EN')) return 'English';
        return 'Unknown';
    }
}
