<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

class OllamaProvider implements AIProvider
{
    public function stream(string $prompt)
    {
        $model = config('services.ollama.model');
        if (!str_contains($model, ':')) {
            $model .= ':latest';
        }

        return Http::withOptions(['stream' => true])
            ->timeout(300)
            ->post(config('services.ollama.url') . '/api/generate', [
                'model' => $model,
                'prompt' => $prompt,
                'stream' => true,
            ]);
    }

    public function parseChunk(string $line): ?string
    {
        $decoded = json_decode($line, true);
        return $decoded['response'] ?? null;
    }
}
