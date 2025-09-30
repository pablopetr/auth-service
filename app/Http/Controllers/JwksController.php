<?php

namespace App\Http\Controllers;

use App\Services\Jwt\JwtksService;
use Illuminate\Http\JsonResponse;

class JwksController extends Controller
{
    public function show(JwtksService $jwtksService): JsonResponse
    {
        return response()->json($jwtksService->jwks());
    }
}
