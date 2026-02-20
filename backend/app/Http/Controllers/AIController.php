<?php

namespace App\Http\Controllers;

use App\Jobs\EvaluateChatJob;
use App\Models\ChatLog;
use App\Models\Document;
use App\Services\OllamaServices;
use App\Services\AI\AIManager;
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
                        $parser = new \Smalot\PdfParser\Parser;
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

        // Chunking lebih pintar: Berdasarkan paragraf atau baris baru agar tidak memotong kata secara paksa
        $chunksRaw = preg_split('/(\n\s*\n|\n)/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $chunks = [];
        $currentChunk = "";
        
        foreach ($chunksRaw as $segment) {
            if (strlen($currentChunk) + strlen($segment) < 1200) {
                $currentChunk .= $segment . "\n";
            } else {
                if (!empty(trim($currentChunk))) $chunks[] = trim($currentChunk);
                $currentChunk = $segment . "\n";
            }
        }
        if (!empty(trim($currentChunk))) $chunks[] = trim($currentChunk);

        foreach ($chunks as $chunk) {
            if (empty(trim($chunk))) {
                continue;
            }


            $embedding = $this->ollama->getEmbedding($chunk);
            $vectorString = '['.implode(',', $embedding).']';

            $doc = Document::create([
                'content' => $chunk,
                'metadata' => [
                    'source' => $source,
                    'created_at' => now(),
                    'full_text_hash' => md5($text),
                ],
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
        $vectorString = '['.implode(',', $queryEmbedding).']';

        // Hybrid Search: Vector + Full Text Search
        $vectorResults = Document::query()
            ->select('id', 'content', 'metadata')
            ->orderByRaw('embedding <=> ?::vector', [$vectorString])
            ->limit(5)
            ->get();

        $textQuery = str_replace(['+', '-'], ' ', $userInput);
        $ftsResults = Document::query()
            ->select('id', 'content', 'metadata')
            ->whereRaw("to_tsvector('simple', content) @@ plainto_tsquery('simple', ?)", [$textQuery])
            ->limit(5)
            ->get();

        // Merge and Deduplicate by ID
        $contextRecords = $vectorResults->concat($ftsResults)->unique('id');

        $contextList = '';
        foreach ($contextRecords as $record) {
            $sourceName = $record->metadata['source'] ?? "Dokumen Tanpa Nama";
            $contextList .= "[DOKUMEN: {$sourceName}]:\n".$record->content."\n\n";
        }

        $prompt = "Anda adalah asisten AI yang sangat disiplin dan presisi. Tugas Anda adalah memberikan jawaban yang TEPAT SASARAN berdasarkan KONTEKS.\n\n".
           "PERATURAN KERAS:\n".
           "1. JAWAB DENGAN SINGKAT dan TO-THE-POINT.\n".
           "2. Fokus HANYA pada data yang ditanyakan oleh User. ABAIKAN informasi fitur atau narasi lain yang tidak relevan dengan pertanyaan.\n".
           "3. Jika pertanyaan meminta list data (seperti 'apa saja', 'daftar', 'data apa'), berikan dalam bentuk bullet points yang diambil dari teks asli.\n".
           "4. WAJIB mencantumkan nama dokumen di setiap akhir kalimat/poin. Format: [NamaDokumen.txt].\n".
           "5. JANGAN menuliskan kesimpulan atau rangkuman umum jika tidak ditanyakan.\n".
           "6. Jika jawaban tidak ditemukan di KONTEKS, jawab jujur: 'Maaf, informasi spesifik tersebut tidak ditemukan dalam dokumen.'\n\n".
           "KONTEKS DOKUMEN:\n".
           "---------------------\n".$contextList."---------------------\n\n".
           'PERTANYAAN USER: '.$userInput."\n\n".
           'JAWABAN SINGKAT & TEPAT:';



        $ai = AIManager::getProvider();

        return response()->stream(function () use ($prompt, $contextRecords, $userInput, $ai) {
            $response = $ai->stream($prompt);
            $body = $response->toPsrResponse()->getBody();

            // Kirim metadata di awal
            echo json_encode([
                'type' => 'metadata',
                'context' => $contextRecords,
                'model' => env('AI_PROVIDER', 'ollama'),
            ])."\n";

            if (ob_get_level() > 0) {
                ob_flush();
            }
            flush();

            $fullAnswer = '';
            while (! $body->eof()) {
                $line = \GuzzleHttp\Psr7\Utils::readLine($body);
                if ($line) {
                    $chunk = $ai->parseChunk($line);

                    if (!empty($chunk)) {
                        $fullAnswer .= $chunk;

                        echo json_encode([
                            'type' => 'content',
                            'content' => $chunk,
                            'done' => false, // Simplified for stream
                        ])."\n";
                        
                        if (ob_get_level() > 0) {
                            ob_flush();
                        }
                        flush();
                    }
                }
            }

            // Simpan log & jalankan evaluasi (RAGAS-like)
            $log = ChatLog::create([
                'question' => $userInput,
                'context' => $contextRecords->toArray(),
                'answer' => $fullAnswer,
                'model' => env('AI_PROVIDER', 'ollama'),
            ]);

            EvaluateChatJob::dispatch($log);
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no', // Penting untuk Nginx/Proxy
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
        ]);
    }
}
