<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Membership extends Model
{
    use SoftDeletes;
    use HasFactory;

    protected $fillable = [
        'user_id',
        'ends_at',
        'ended_at',
    ];
}
