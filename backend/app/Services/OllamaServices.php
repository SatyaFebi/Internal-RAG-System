<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaServices
{
   protected $baseUrl;

    public function __construct() {
       $this->baseUrl = config("services.ollama.url");
    }

    public function getEmbedding(string $text)
    {
       $model = config('services.ollama.embed_model') ?? 'mxbai-embed-large';
       
       $response = HTTP::post("{$this->baseUrl}/api/embeddings", [
          'model' => $model,
          'prompt' => $text,
       ]);

       if ($response->failed()) {
           throw new \Exception("Ollama request failed: " . ($response->json()['error'] ?? 'Unknown error'));
       }

       $result = $response->json();

       if (!isset($result['embedding'])) {
           throw new \Exception("Ollama response missing 'embedding' key. Full response: " . json_encode($result));
       }

       return $result['embedding'];
    }
}

