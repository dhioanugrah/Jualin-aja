<?php

namespace App\Enums;

enum AdPlacement: string
{
    case HomeTop = 'home_top';
    case CatalogInline = 'catalog_inline';

    public function label(): string
    {
        return match ($this) {
            self::HomeTop => 'Beranda atas',
            self::CatalogInline => 'Sela katalog',
        };
    }
}
