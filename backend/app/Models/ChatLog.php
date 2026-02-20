<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatLog extends Model
{
    protected $fillable = [
        'question',
        'context',
        'answer',
        'model',
        'faithfulness_score',
        'relevance_score',
        'evaluation_reason',
    ];

    protected $casts = [
        'context' => 'array',
    ];
}
