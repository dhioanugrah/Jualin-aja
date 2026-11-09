<?php

namespace App\Enums;

enum ViolationType: string
{
    case NoConfirm = 'no_confirm';
    case NoShow = 'no_show';
    case LateCancel = 'late_cancel';

    public function label(): string
    {
        return match ($this) {
            self::NoConfirm => 'Tidak konfirmasi kehadiran',
            self::NoShow => 'Tidak datang',
            self::LateCancel => 'Batal mendadak',
        };
    }
}
