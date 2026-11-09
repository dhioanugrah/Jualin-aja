<?php

namespace App\Enums;

enum HandoverMethod: string
{
    case Cod = 'cod';
    case Shipping = 'shipping';

    public function label(): string
    {
        return match ($this) {
            self::Cod => 'COD',
            self::Shipping => 'Pengiriman',
        };
    }
}
