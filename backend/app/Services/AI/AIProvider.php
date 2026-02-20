<?php

namespace App\Services\AI;

interface AIProvider
{
    /**
     * @return \Illuminate\Http\Client\Response
     */
    public function stream(string $prompt);

    /**
     * Parse a single line from the stream response.
     */
    public function parseChunk(string $line): ?string;
}
