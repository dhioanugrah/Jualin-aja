<?php

namespace App\Enums;

enum ViolationStatus: string
{
    case Active = 'active';
    case Disputed = 'disputed';
    case Revoked = 'revoked';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Aktif',
            self::Disputed => 'Dibantah',
            self::Revoked => 'Dicabut',
        };
    }
}
