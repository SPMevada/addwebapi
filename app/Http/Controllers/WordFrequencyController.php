<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class WordFrequencyController extends Controller
{
    public function analyze(Request $request) {
        $validated = $request->validate([
            'text' => 'required_without:file|string',
            'file' => 'required_without:text|file|mimes:txt|max:10000',
            'top' => 'required|integer|min:1',
            'exclude' => 'nullable|array',
            'exclude.*' => 'string',
        ]);

        $data = !empty($request->all()) ? $request->all() : [];
        $textData = !empty($data['text']) ? $data['text'] : '';

        if($request->hasFile('file')) {
            $uploadFile = $request->file('file')->getRealPath();
        }

        $text = !empty($textData) ? $textData : file_get_contents($uploadFile);

        $exclude = !empty($data['exclude']) ? $data['exclude'] : [];
        $frequencies = $this->getWordFrequencies($text, $exclude);

        $cacheKey = md5($text);
        if (Cache::has($cacheKey)) {
            return response()->json(Cache::get($cacheKey));
        }

        $top = !empty($data['top']) ? $data['top'] : '';
        
        $topWords = array_slice($frequencies, 0, $top);
        Cache::put($cacheKey, $topWords, now()->addMinutes(10));
        return response()->json(['data' => $topWords]);
    }

    private function getWordFrequencies($text, $exclude) {
        $text = strtolower(preg_replace('/[^a-z0-9\s]/', '', $text));
        $words = preg_split('/\s+/', $text);
        $frequencies = [];

        foreach ($words as $word) {
            if (in_array($word, $exclude)) {
                continue;
            }
            $frequencies[$word] = isset($frequencies[$word]) ? $frequencies[$word] + 1 : 1;
        }

        arsort($frequencies);
        return $frequencies;
    }
}
 