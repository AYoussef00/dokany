<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiteVisit extends Model
{
    protected $table = 'site_visits';

    public $timestamps = false;

    protected $fillable = [
        'visited_at',
        'user_id',
        'session_hash',
        'ip',
        'country_code',
        'country_name',
        'path',
        'user_agent',
    ];

    protected $casts = [
        'visited_at' => 'datetime',
        'user_id' => 'integer',
    ];
}

