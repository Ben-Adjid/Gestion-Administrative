<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PageViewController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'visitor_id' => ['required', 'uuid'],
            'path' => ['required', 'string', 'max:255', 'starts_with:/'],
        ]);

        if (str_starts_with($validated['path'], '/admin')) {
            return response()->json(['message' => 'Les pages privées ne sont pas suivies.'], 422);
        }

        DB::table('page_views')->insert([
            'path' => $validated['path'],
            'visitor_hash' => hash_hmac('sha256', $validated['visitor_id'], (string) config('app.key')),
            'viewed_at' => now(),
        ]);

        return response()->json(['recorded' => true], 202);
    }
}