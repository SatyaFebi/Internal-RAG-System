<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

$apiKey = config('services.gemini.api_key');
$url = "https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}";
$response = Http::get($url);

if ($response->successful()) {
    foreach ($response->json()['models'] as $model) {
        echo $model['name'] . "\n";
    }
} else {
    echo "Error: " . $response->status() . " - " . $response->body() . "\n";
}
