<?php

namespace App\Enums;

enum BookingStatus: string
{
    case Pending = 'pending';
    case Accepted = 'accepted';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';
    case Expired = 'expired';
    case Disputed = 'disputed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Accepted => 'Diterima',
            self::Rejected => 'Ditolak',
            self::Completed => 'Selesai',
            self::Cancelled => 'Dibatalkan',
            self::Expired => 'Kedaluwarsa',
            self::Disputed => 'Sengketa',
        };
    }

    /**
     * Kelas Tailwind untuk badge status.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Accepted => 'bg-brand-50 text-brand-700 ring-brand-200',
            self::Rejected => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Completed => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::Cancelled => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Expired => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Disputed => 'bg-red-50 text-red-700 ring-red-200',
        };
    }
}
