<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CsrfTokenController extends Controller
{
    public function token(): JsonResponse
    {
        return response()->json([
            'token' => csrf_token(),
        ]);
    }
}
