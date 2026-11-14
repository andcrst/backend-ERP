<?php

namespace App\Enums;

/**
 * Status pembayaran transaksi.
 */
enum StatusPembayaran: string
{
    case Lunas = 'LUNAS';
    case BelumLunas = 'BELUM_LUNAS';

    public function label(): string
    {
        return match ($this) {
            self::Lunas => 'Lunas',
            self::BelumLunas => 'Belum Lunas',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
