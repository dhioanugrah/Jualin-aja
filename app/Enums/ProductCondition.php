<?php

namespace App\Enums;

enum ProductCondition: string
{
    case New = 'new';
    case LikeNew = 'like_new';
    case Good = 'good';
    case Fair = 'fair';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::LikeNew => 'Sangat Baik',
            self::Good => 'Baik',
            self::Fair => 'Cukup',
        };
    }
}
