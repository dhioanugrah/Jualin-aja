<?php

namespace App\Enums;

enum OfferStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Accepted => 'Diterima',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
        };
    }

    /**
     * Kelas Tailwind untuk badge status.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Accepted => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Rejected => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Cancelled => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Expired => 'bg-slate-100 text-slate-600 ring-slate-200',
        };
    }
}
