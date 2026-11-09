<?php

namespace App\Enums;

enum UserMode: string
{
    case Buyer = 'buyer';
    case Seller = 'seller';

    public function label(): string
    {
        return match ($this) {
            self::Buyer => 'Pembeli',
            self::Seller => 'Penjual',
        };
    }
}
