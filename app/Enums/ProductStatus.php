<?php

namespace App\Enums;

enum ProductStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case NeedsRevision = 'needs_revision';
    case Sold = 'sold';
    case Hidden = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Moderasi',
            self::Approved => 'Disetujui',
            self::NeedsRevision => 'Perlu Perbaikan',
            self::Sold => 'Terjual',
            self::Hidden => 'Disembunyikan',
        };
    }

    /**
     * Kelas Tailwind untuk badge status.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-amber-50 text-amber-700 ring-amber-200',
            self::Approved => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
            self::NeedsRevision => 'bg-red-50 text-red-700 ring-red-200',
            self::Sold => 'bg-slate-100 text-slate-600 ring-slate-200',
            self::Hidden => 'bg-red-100 text-red-800 ring-red-300',
        };
    }
}
