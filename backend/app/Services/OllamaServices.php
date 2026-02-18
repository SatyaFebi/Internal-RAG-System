<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class OllamaServices
{
   protected $baseUrl;

   public function __construct() {
      $this->baseUrl = config("");
   }

   public function getEmbedding(string $text)
   {
      $response = HTTP::post("{$this->baseUrl}/api/embeddings", [
         'model' => 'mxbai-embed-large',
         'prompt' => $text,
      ]);

      return $response->json();
   }
}