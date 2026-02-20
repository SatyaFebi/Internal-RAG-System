<?php

namespace App\Services;

use App\Models\ChatLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class RagasService
{
    public function evaluate(ChatLog $log)
    {
        $context = collect($log->context)->pluck('content')->implode("\n");

        $prompt = "Tugas Anda adalah mengevaluasi kualitas sistem RAG (Retrieval Augmented Generation).\n\n".
            "PERTANYAAN: {$log->question}\n".
            "KONTEKS:\n{$context}\n".
            "JAWABAN MODEL: {$log->answer}\n\n".
            "Berikan skor dari 0.0 sampai 1.0 untuk metrik berikut dalam format JSON:\n".
            "1. faithfulness: Seberapa setia jawaban terhadap konteks (tidak ada halusinasi)?\n".
            "2. relevance: Seberapa relevan jawaban terhadap pertanyaan?\n".
            "3. reason: Singkat saja alasannya.\n\n".
            'HANYA RESPON JSON, CONTOH: {"faithfulness": 0.9, "relevance": 1.0, "reason": "Semua informasi ada di sumber."}';

        try {
            $model = config('services.ollama.model');
            if (!str_contains($model, ':')) {
                $model .= ':latest';
            }

            $response = Http::timeout(60)->post(config('services.ollama.url').'/api/generate', [
                'model' => $model,
                'prompt' => $prompt,

                'stream' => false,
                'format' => 'json',
            ]);

            $jsonResponse = $response->json();
            $data = json_decode($jsonResponse['response'], true);

            $log->update([
                'faithfulness_score' => $data['faithfulness'] ?? 0,
                'relevance_score' => $data['relevance'] ?? 0,
                'evaluation_reason' => $data['reason'] ?? 'Evaluated',
            ]);
        } catch (\Exception $e) {
            Log::error('RAGAS Eval Failed: '.$e->getMessage());
        }
    }
}
