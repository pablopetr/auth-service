<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MeController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->attributes->get('jwt_user');
        $claims = $request->attributes->get('jwt_claims', []);

        return response()->json([
            'id' => $user->id,
            'email' => $user->email,
            'membership' => $claims['membership'] ?? null,
            'aud' => $claims['aud'] ?? [],
            'scope' => $claims['scope'] ?? [], // <- sempre retorna os escopos do token
            'iat' => $claims['iat'] ?? null,
            'exp' => $claims['exp'] ?? null,
        ]);
    }
}
