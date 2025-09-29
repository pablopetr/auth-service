<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JwtKey extends Model
{
    protected $fillable = [
        'kid',
        'public_pem',
        'private_pem_encrypted',
        'active',
        'deprecates_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'deprecates_at' => 'datetime',
    ];
}
