<?php

namespace App\Http\Controllers;

use App\Services\Jwt\JwtksService;
use Illuminate\Http\JsonResponse;

class JwksController extends Controller
{
    public function show(): JsonResponse
    {
        $jwtService = new JwtksService();

        return response()->json($jwtService->jwks());
    }
}
