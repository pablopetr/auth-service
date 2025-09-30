<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\JwksController;
use App\Http\Middleware\AuthenticateJwt;

// Auth endpoints
Route::post('/auth/signup', [AuthController::class, 'signup']);
Route::post('/auth/login',  [AuthController::class, 'login'])->middleware('throttle:6,1');   // 6/min
Route::post('/auth/refresh',[AuthController::class, 'refresh'])->middleware('throttle:12,1'); // 12/min
Route::post('/auth/logout', [AuthController::class, 'logout']);

Route::get('/.well-known/jwks.json', [JwksController::class, 'show']);

// Rota protegida de exemplo
Route::get('/me', function (Request $request) {
    /** @var \App\Models\User $user */
    $user = $request->attributes->get('auth_user');
    return ['id' => $user->id, 'email' => $user->email];
})->middleware([AuthenticateJwt::class.':internal']);
