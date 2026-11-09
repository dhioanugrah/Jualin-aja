<?php

namespace App\Enums;

enum ReviewDirection: string
{
    case BuyerToSeller = 'buyer_to_seller';
    case SellerToBuyer = 'seller_to_buyer';

    public function label(): string
    {
        return match ($this) {
            self::BuyerToSeller => 'Pembeli menilai penjual',
            self::SellerToBuyer => 'Penjual menilai pembeli',
        };
    }
}
