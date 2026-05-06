<?php

declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use App\Models\PageView;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageViewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'path' => ['required', 'string', 'max:512'],
            'started_at' => ['required', 'date'],
            'duration_seconds' => ['required', 'integer', 'min:0', 'max:86400'],
            'referrer' => ['nullable', 'string', 'max:512'],
        ]);

        $sessionId = (string) $request->session()->getId();
        if ($sessionId === '') {
            return response()->json(['ok' => true]);
        }

        // Only store meaningful durations (avoid noisy 0s).
        $duration = (int) $data['duration_seconds'];
        if ($duration < 1) {
            return response()->json(['ok' => true]);
        }

        PageView::query()->create([
            'started_at' => $data['started_at'],
            'user_id' => $request->user()?->id,
            'session_hash' => hash('sha256', $sessionId),
            'path' => '/'.ltrim((string) $data['path'], '/'),
            'duration_seconds' => $duration,
            'referrer' => $data['referrer'] ?? null,
            'user_agent' => substr((string) $request->userAgent(), 0, 512) ?: null,
            'ip' => $request->ip(),
        ]);

        return response()->json(['ok' => true]);
    }
}

