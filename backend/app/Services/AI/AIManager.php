<?php

namespace App\Services\AI;

class AIManager
{
    public static function getProvider(): AIProvider
    {
        $driver = env('AI_PROVIDER', 'ollama');

        return match ($driver) {
            'gemini' => new GeminiProvider(),
            default => new OllamaProvider(),
        };
    }
}
