<?php

namespace App\Enums;

enum ReportReason: string
{
    case Fraud = 'fraud';
    case Prohibited = 'prohibited';
    case FakeInfo = 'fake_info';
    case FakeBuyer = 'fake_buyer';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Fraud => 'Penipuan',
            self::Prohibited => 'Barang terlarang',
            self::FakeInfo => 'Info palsu',
            self::FakeBuyer => 'Pembeli fiktif',
            self::Other => 'Lainnya',
        };
    }
}
