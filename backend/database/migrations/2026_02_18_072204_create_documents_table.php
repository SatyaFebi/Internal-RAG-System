<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS vector');

        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->text('content'); // Simpen chunks
            $table->jsonb('metadata')->nullable(); // Simpen info asal file/poge
            $table->timestamps();
        });

         // Tambah kolom embedding
         DB::statement('ALTER TABLE documents ADD COLUMN embedding vector(1024);');

         // Tambah index HNSW biar pencarian AI cepet
         DB::statement('CREATE INDEX on documents USING hnsw (embedding vector_cosine_ops)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
