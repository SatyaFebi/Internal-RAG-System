<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class GeminiProvider implements AIProvider
{
    public function stream(string $prompt)
    {
        $apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.5-flash-lite');
        // Gunakan v1 dan tambahkan alt=sse agar output tidak diprint cantik (pretty printed)
        // Ini membuat parsing baris per baris jauh lebih stabil
        $url = "https://generativelanguage.googleapis.com/v1/models/{$model}:streamGenerateContent?key={$apiKey}&alt=sse";


        $response = Http::withOptions(['stream' => true])
            ->timeout(60)
            ->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ]
            ]);

        if ($response->failed()) {
            \Illuminate\Support\Facades\Log::error("Gemini API Error ({$response->status()}): " . $response->body());
        }

        return $response;
    }


    public function parseChunk(string $line): ?string
    {
        $line = trim($line);
        
        // Lewati baris kosong
        if (empty($line)) {
            return null;
        }

        // Jika menggunakan alt=sse, Gemini mengirim data dengan awalan 'data: '
        if (str_starts_with($line, 'data: ')) {
            $line = substr($line, 6);
        }

        // Lewati jika baris bukan JSON (misal komentar SSE atau baris kosong)
        if (!str_starts_with($line, '{')) {
            return null;
        }

        $decoded = json_decode($line, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return null;
        }

        return $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
    }

}
