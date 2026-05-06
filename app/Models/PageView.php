<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageView extends Model
{
    protected $table = 'page_views';

    public $timestamps = false;

    protected $fillable = [
        'started_at',
        'user_id',
        'session_hash',
        'path',
        'duration_seconds',
        'referrer',
        'user_agent',
        'ip',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'user_id' => 'integer',
        'duration_seconds' => 'integer',
    ];
}

