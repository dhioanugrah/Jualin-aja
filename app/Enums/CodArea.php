<?php

namespace App\Enums;

enum CodArea: string
{
    case Inside = 'inside';
    case Around = 'around';

    public function label(): string
    {
        return match ($this) {
            self::Inside => 'Dalam ITK',
            self::Around => 'Sekitar ITK',
        };
    }
}
