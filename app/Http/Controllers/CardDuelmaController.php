<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\CardDuelma;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
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

        $endpoint = 'https://duelmasters.fandom.com/api.php';

        // 1. Search Fandom API
        $searchResponse = Http::withHeaders([
            'User-Agent' => 'MDS_OCG_Community_Tool/1.0 (contact@mds.local)',
        ])->get($endpoint, [
            'action'      => 'query',
            'list'        => 'search',
            'srsearch'    => "{$cleanSet} \"{$rawNum}\"",
            'srnamespace' => 0,
            'srlimit'     => 5,
            'format'      => 'json',
        ]);

        $searchResults = $searchResponse->json('query.search', []);

        if (empty($searchResults)) {
            return response()->json([
                'success' => false,
                'message' => "No card found for {$rawSet} #{$rawNum} on Duel Masters Fandom Wiki.",
            ], 404);
        }

        // Pick card article rather than booster set overview
        $pageTitle = null;
        foreach ($searchResults as $result) {
            $t = $result['title'];
            if (Str::startsWith($t, 'DM') && Str::contains($t, ['Fantasy', 'Pack', 'Deck', 'BEST', 'Booster', 'List', 'Theme'])) {
                continue;
            }
            $pageTitle = $t;
            break;
        }
        if (!$pageTitle) {
            $pageTitle = $searchResults[0]['title'];
        }

        // 2. Fetch page wikitext
        $pageResponse = Http::withHeaders([
            'User-Agent' => 'MDS_OCG_Community_Tool/1.0 (contact@mds.local)',
        ])->get($endpoint, [
            'action'  => 'query',
            'titles'  => $pageTitle,
            'prop'    => 'revisions',
            'rvprop'  => 'content',
            'format'  => 'json',
        ]);

        $pages = $pageResponse->json('query.pages', []);
        $pageData = reset($pages);
        $wikitext = $pageData['revisions'][0]['*'] ?? '';

        // Handle redirects
        if (preg_match('/#REDIRECT\s*\[\[(.*?)\]\]/i', $wikitext, $redir)) {
            $pageTitle = trim($redir[1]);
            $pageResponse = Http::withHeaders([
                'User-Agent' => 'MDS_OCG_Community_Tool/1.0 (contact@mds.local)',
            ])->get($endpoint, [
                'action'  => 'query',
                'titles'  => $pageTitle,
                'prop'    => 'revisions',
                'rvprop'  => 'content',
                'format'  => 'json',
            ]);
            $pages = $pageResponse->json('query.pages', []);
            $pageData = reset($pages);
            $wikitext = $pageData['revisions'][0]['*'] ?? '';
        }

        // 3. Exact field extractor
        $extract = function ($fields) use ($wikitext) {
            if (!is_array($fields)) {
                $fields = [$fields];
            }
            foreach ($fields as $field) {
                if (preg_match('/\|\s*' . preg_quote($field, '/') . '\s*=\s*(.*?)(?=\n\s*\||\n\s*\}\}|$)/s', $wikitext, $m)) {
                    $raw = trim($m[1]);
                    if ($raw === '') continue;

                    // Expand {{Shield Trigger|...}} -> [Shield Trigger]
                    $cleaned = preg_replace('/\{\{Shield Trigger(?:\|[^}]*)?\}\}/i', '[Shield Trigger]', $raw);
                    // Strip {{Ruby|Kanji|Hiragana}} -> Kanji
                    $cleaned = preg_replace('/\{\{Ruby\|([^\|\}]+)\|[^\}]*\}\}/i', '$1', $cleaned);
                    // Strip remaining templates {{...}}
                    $cleaned = preg_replace('/\{\{[^}]+\}\}/', '', $cleaned);
                    // Strip wiki links: [[Link|Text]] -> Text, [[Text]] -> Text
                    $cleaned = preg_replace('/\[\[(?:[^|\]]*\|)?([^\]]+)\]\]/', '$1', $cleaned);
                    $cleaned = trim(strip_tags($cleaned));

                    if (!empty($cleaned)) {
                        return $cleaned;
                    }
                }
            }
            return null;
        };

        // 4. Download Card Image via the '| image =' field
        $localImagePath = null;
        $rawImageName = $extract(['image', 'image1']);

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

        // 5. Extract Details matching Cardtable keys
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
        ]);
    }
}
