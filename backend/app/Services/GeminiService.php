<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key') ?? '';
        $this->model = config('services.gemini.model') ?? 'gemini-2.5-flash-lite';
    }

    public function streamGenerateContent(string $prompt)
    {
        $url = "https://generativelanguage.googleapis.com/v1/models/{$this->model}:streamGenerateContent?key={$this->apiKey}";

        return Http::withOptions(['stream' => true])
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
    }
}
