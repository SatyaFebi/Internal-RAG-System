<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\OllamaServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class AIController extends Controller
{
    protected $ollama;

    public function __construct(OllamaServices $ollama)
    {
         $this->ollama = $ollama;
    }

    public function store(Request $request)
    {
        $request->validate([
            'content' => 'nullable|string',
            'file' => 'nullable|file|mimes:pdf,txt,md|max:10240', // 10MB max
        ]);

        $text = '';
        $source = 'manual_input';

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $source = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension();

            if ($extension === 'txt' || $extension === 'md') {
                $text = file_get_contents($file->getRealPath());
            } elseif ($extension === 'pdf') {
                if (class_exists(\Smalot\PdfParser\Parser::class)) {
                    try {
                        $parser = new \Smalot\PdfParser\Parser();
                        $pdf = $parser->parseFile($file->getRealPath());
                        $text = $pdf->getText();
                    } catch (\Exception $e) {
                        return response()->json(['status' => 'error', 'message' => 'Gagal membaca file PDF. Pastikan format benar.'], 422);
                    }
                } else {
                    return response()->json(['status' => 'error', 'message' => 'Library PDF Parser belum terinstall. Untuk saat ini gunakan file .txt atau .md.'], 422);
                }
            }

        } else {
            $text = $request->input('content');
        }

        if (empty($text)) {
            return response()->json(['status' => 'error', 'message' => 'Tidak ada teks yang diupload atau diproses.'], 422);
        }

        // Chunking sederhana (per 1000 karakter misal)
        $chunks = str_split($text, 1000);

        foreach ($chunks as $chunk) {
            if (empty(trim($chunk))) continue;

            $embedding = $this->ollama->getEmbedding($chunk);
            $vectorString = '[' . implode(',', $embedding) . ']';

            $doc = Document::create([
                'content' => $chunk,
                'metadata' => [
                    'source' => $source,
                    'created_at' => now(),
                    'full_text_hash' => md5($text)
                ]
            ]);

            DB::statement('UPDATE documents SET embedding = ? WHERE id = ?', [$vectorString, $doc->id]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Dokumen berhasil dipelajari oleh AI!',
        ]);
    }


    public function chat(Request $request)
    {
      $userInput = $request->input('message');

      $queryEmbedding = $this->ollama->getEmbedding($userInput);
      $vectorString = '[' . implode(',', $queryEmbedding) . ']';

      $contextRecords = DB::table('documents')
         ->select('content')
         ->orderByRaw("embedding <=> ?::vector", [$vectorString])
         ->limit(5)
         ->get();

      $contextText = $contextRecords->pluck('content')->implode("\n");

      $prompt = "Anda adalah asisten AI yang hanya boleh menjawab berdasarkan dokumen yang diberikan. \n" .
         "Gunakan konteks di bawah ini untuk menjawab pertanyaan User. \n" .
         "Jika jawaban tidak ada di dalam konteks, jawab saja bahwa Anda tidak menemukan informasi tersebut di dokumen basis pengetahuan. \n" .
         "DILARANG memberikan jawaban dari pengetahuan umum Anda sendiri.\n\n" .
         "KONTEKS:\n" .
         "---------------------\n" . $contextText . "\n---------------------\n\n" .
         "PERTANYAAN: " . $userInput . "\n\n" .
         "JAWABAN:";


      $model = config('services.ollama.model');
      if (!str_contains($model, ':')) {
          $model .= ':latest';
      }

      $response = Http::timeout(300)->post(config('services.ollama.url') . '/api/generate', [
         'model' => $model,
         'prompt' => $prompt,
         'stream' => false,
      ]);

      return response()->json([
          'answer' => $response->json()['response'] ?? 'Gagal mendapatkan jawaban dari AI.',
          'context_used' => $contextRecords,
      ]);

    }
}

