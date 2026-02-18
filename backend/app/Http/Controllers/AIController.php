<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\OllamaServices;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AIController extends Controller
{
    protected $ollama;

    public function __construct(OllamaServices $ollama)
    {
         $this->ollama = $ollama;
    }

    public function store(Request $request)
    {
         $request->validate(['content' => 'required|string']);
         $text = $request->input('content');


         // Dapetin embedding dari ollama
         $embedding = $this->ollama->getEmbedding($text);

         // Simpen ke database
         $vectorString = '[' . implode(',', $embedding) . ']';

         $doc = Document::create([
            'content' => $text,
            'metadata' => ['source' => 'manual_input', 'created_at' => now()]
         ]);

         DB::statement('UPDATE documents SET embedding = ? WHERE id = ?', [$vectorString, $doc->id]);

         return response()->json([
            'status' => 'success',
            'message' => 'Teks berhasil disimpan ke memori AI!',
            'data' => $doc
         ]);
    }
}
