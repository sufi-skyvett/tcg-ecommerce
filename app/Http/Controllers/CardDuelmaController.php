<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardDuelma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CardDuelmaController extends Controller
{
    public function fetchFromFandom(Request $request)
    {
        $request->validate([
            'set_code' => 'required|string|max:50',
            'collector_number' => 'required|string|max:50',
        ]);

        $rawSet = trim($request->input('set_code'));
        $rawNum = trim($request->input('collector_number'));

        $cleanSet = strtoupper(str_replace(['-', ' '], '', $rawSet));
        if (!Str::startsWith($cleanSet, 'DM')) {
            $cleanSet = 'DM' . $cleanSet;
        }

        // 1. DATABASE CHECK FIRST (Saves Fandom API calls)
        $existing = CardDuelma::where('set_code', $cleanSet)
            ->where('collector_number', $rawNum)
            ->first();

        // If card exists and user didn't explicitly request a fresh sync, return DB data directly
        if ($existing && !$request->boolean('force_refresh')) {
            return response()->json([
                'success' => true,
                'card'    => $existing,
                'source'  => 'database',
            ]);
        }

        // 2. GLOBAL OUTBOUND RATE LIMIT (Max 20 requests per 60 seconds to Fandom)
        $limiterKey = 'fandom-api-global';
        $maxAttempts = 20;
        $decaySeconds = 60;

        if (RateLimiter::tooManyAttempts($limiterKey, $maxAttempts)) {
            $secondsRemaining = RateLimiter::availableIn($limiterKey);

            return response()->json([
                'success' => false,
                'message' => "Fandom Wiki query limit reached. Please wait {$secondsRemaining} seconds before searching new cards.",
                'retry_after' => $secondsRemaining,
            ], 429);
        }

        // Count this execution against the rate limit
        RateLimiter::hit($limiterKey, $decaySeconds);

        $endpoint = 'https://duelmasters.fandom.com/api.php';

        // 3. Search API with Fallback Queries
        $searchQueries = [
            "{$cleanSet} \"{$rawNum}\"", // Strict exact match search
            "{$cleanSet} {$rawNum}"      // Fallback loose search if the first fails
        ];

        $validWikitext = null;
        $pageTitle = null;

        foreach ($searchQueries as $queryStr) {
            $searchResponse = Http::withHeaders([
                'User-Agent' => 'MDS_OCG_Community_Tool/1.0 (contact@mds.local)',
            ])->get($endpoint, [
                'action'      => 'query',
                'list'        => 'search',
                'srsearch'    => $queryStr,
                'srnamespace' => 0,
                'srlimit'     => 3, // Reduced from 5 to minimize revision queries
                'format'      => 'json',
            ]);

            $searchResults = $searchResponse->json('query.search', []);

            foreach ($searchResults as $result) {
                $t = $result['title'];

                // Skip obvious set overviews or artwork gallery pages
                if (Str::startsWith($t, 'DM') && Str::contains($t, ['Fantasy', 'Pack', 'Deck', 'BEST', 'Booster', 'List', 'Theme', 'Artwork'])) {
                    continue;
                }

                // Fetch page wikitext
                $pageResponse = Http::withHeaders([
                    'User-Agent' => 'MDS_OCG_Community_Tool/1.0 (contact@mds.local)',
                ])->get($endpoint, [
                    'action'  => 'query',
                    'titles'  => $t,
                    'prop'    => 'revisions',
                    'rvprop'  => 'content',
                    'format'  => 'json',
                ]);

                $pages = $pageResponse->json('query.pages', []);
                $pageData = reset($pages);
                $wikitext = $pageData['revisions'][0]['*'] ?? '';

                // Handle redirects
                if (preg_match('/#REDIRECT\s*\[\[(.*?)\]\]/i', $wikitext, $redir)) {
                    $t = trim($redir[1]);
                    $pageResponse = Http::withHeaders([
                        'User-Agent' => 'MDS_OCG_Community_Tool/1.0 (contact@mds.local)',
                    ])->get($endpoint, [
                        'action'  => 'query',
                        'titles'  => $t,
                        'prop'    => 'revisions',
                        'rvprop'  => 'content',
                        'format'  => 'json',
                    ]);
                    $pages = $pageResponse->json('query.pages', []);
                    $pageData = reset($pages);
                    $wikitext = $pageData['revisions'][0]['*'] ?? '';
                }

                // Verify that this is a REAL card page by looking for the Cardtable
                if (stripos($wikitext, '{{Cardtable') !== false) {
                    $validWikitext = $wikitext;
                    $pageTitle = $t;
                    break 2; // Found it! Break entirely out of both loops
                }
            }
        }

        if (!$validWikitext) {
            return response()->json([
                'success' => false,
                'message' => "No valid card data found for {$rawSet} #{$rawNum} on Duel Masters Fandom Wiki.",
            ], 404);
        }

        $wikitext = $validWikitext;

        // 4. Exact field extractor
        $extract = function ($fields) use ($wikitext) {
            if (!is_array($fields)) {
                $fields = [$fields];
            }
            foreach ($fields as $field) {
                if (preg_match('/\|\s*' . preg_quote($field, '/') . '\s*=\s*(.*?)(?=\n\s*\||\n\s*\}\}|$)/s', $wikitext, $m)) {
                    $raw = trim($m[1]);
                    if ($raw === '') continue;

                    // Expand specific templates if needed
                    $cleaned = preg_replace('/\{\{Shield Trigger(?:\|[^}]*)?\}\}/i', '[Shield Trigger]', $raw);

                    // Keep the primary Kanji for Ruby templates {{Ruby|Kanji|Hiragana}} -> Kanji
                    $cleaned = preg_replace('/\{\{Ruby\|([^\|\}]+)\|[^\}]*\}\}/i', '$1', $cleaned);

                    // Convert remaining templates to brackets E.g., {{Shinkarise}} -> [Shinkarise]
                    $cleaned = preg_replace('/\{\{\s*([^}|]+)(?:\|[^}]*)?\}\}/', '[$1]', $cleaned);

                    // Strip wiki links: [[Link|Text]] -> Text, [[Text]] -> Text
                    $cleaned = preg_replace('/\[\[(?:[^|\]]*\|)?([^\]]+)\]\]/', '$1', $cleaned);

                    // Remove any remaining HTML tags
                    $cleaned = trim(strip_tags($cleaned));

                    if (!empty($cleaned)) {
                        return $cleaned;
                    }
                }
            }
            return null;
        };

        // 5. Download Card Image via the '| image =' field
        $localImagePath = null;
        $rawImageName = $extract(['image', 'image1']);

        // FALLBACK: If standard image field is missing, grab the first image from a <gallery>
        if (!$rawImageName && preg_match('/<gallery>\s*([^|\n]+?\.(?:jpg|png|jpeg|webp))/is', $wikitext, $galleryMatch)) {
            $rawImageName = trim($galleryMatch[1]);
        }

        if ($rawImageName) {
            // Strip any existing 'File:' and trim whitespace
            $cleanFileName = trim(preg_replace('/^File:/i', '', $rawImageName));

            // Capitalize the first letter (Mandatory for MediaWiki title lookups)
            $cleanFileName = ucfirst($cleanFileName);
            $targetFileTitle = 'File:' . $cleanFileName;

            $imgInfoRes = Http::withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            ])->get($endpoint, [
                'action' => 'query',
                'titles' => $targetFileTitle,
                'prop'   => 'imageinfo',
                'iiprop' => 'url',
                'format' => 'json',
            ]);

            $filePages = $imgInfoRes->json('query.pages', []);
            $firstFile = reset($filePages);

            // Retrieve direct image URL or fallback to revision thumbnail
            $remoteImageUrl = $firstFile['imageinfo'][0]['url'] ?? null;

            // Fallback: If original URL has query tracking, clean it
            if ($remoteImageUrl) {
                $remoteImageUrl = strtok($remoteImageUrl, '?');

                $imgStream = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                    'Referer'    => 'https://duelmasters.fandom.com/',
                ])->get($remoteImageUrl);

                if ($imgStream->successful() && extension_loaded('gd')) {
                    $srcImage = @imagecreatefromstring($imgStream->body());
                    if ($srcImage) {
                        $origW = imagesx($srcImage);
                        $origH = imagesy($srcImage);
                        $targetW = 320;
                        $targetH = (int) ($origH * ($targetW / $origW));

                        $resized = imagecreatetruecolor($targetW, $targetH);
                        imagecopyresampled($resized, $srcImage, 0, 0, 0, 0, $targetW, $targetH, $origW, $origH);

                        $safeFileName = 'duel_masters/' . Str::slug($cleanSet . '-' . str_replace('/', '-', $rawNum)) . '.jpg';

                        ob_start();
                        imagejpeg($resized, null, 80);
                        $compressedData = ob_get_clean();

                        imagedestroy($srcImage);
                        imagedestroy($resized);

                        // Ensure target directory exists on public disk
                        Storage::disk('public')->makeDirectory('duel_masters');
                        Storage::disk('public')->put($safeFileName, $compressedData);

                        $localImagePath = $safeFileName;
                    }
                }
            }
        }

        // 6. Extract Details matching Cardtable keys
        $cardName = $extract(['name', 'english_name', 'enname']) ?? $pageTitle;

        // Fandom uses 'engtext' for English text, falls back to 'jptext'
        $effect = $extract(['engtext', 'english_text', 'text', 'jptext']);

        $card = CardDuelma::updateOrCreate(
            [
                'set_code'         => $cleanSet,
                'collector_number' => $rawNum,
            ],
            [
                'name'             => $cardName,
                'card_type'        => $extract(['type']) ?? 'Creature',
                'civilization'     => $extract(['civilization', 'civ']),
                'mana_cost'        => $extract(['cost']),
                'races'            => $extract(['race', 'races']),
                'effect_text'      => $effect,
                'image_path'       => $localImagePath,
                'payload'          => $wikitext,
            ]
        );

        return response()->json([
            'success' => true,
            'card'    => $card,
            'source'  => 'api',
        ]);
    }
}
