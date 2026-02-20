<?php

namespace App\Jobs;

use App\Models\ChatLog;
use App\Services\RagasService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class EvaluateChatJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(protected ChatLog $chatLog) {}

    public function handle(RagasService $ragasService): void
    {
        $ragasService->evaluate($this->chatLog);
    }
}
