<?php

namespace App\Enum;

enum UserRole: string
{
    case Admin = 'Admin';
    case User = 'User';

    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }
}
