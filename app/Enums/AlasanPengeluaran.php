<?php

namespace App\Enums;

/**
 * Alasan barang keluar non-penjualan (pengeluaran gudang).
 *
 * Setiap pengeluaran WAJIB beralasan agar perubahan stok dapat ditelusuri
 * (G-05, ERP_BUSINESS_PROCESS_BY_ROLE §3.4).
 */
enum AlasanPengeluaran: string
{
    case Rusak = 'RUSAK';
    case Hilang = 'HILANG';
    case PakaiInternal = 'PAKAI_INTERNAL';
    case ReturSupplier = 'RETUR_SUPPLIER';
    case Lainnya = 'LAINNYA';

    public function label(): string
    {
        return match ($this) {
            self::Rusak => 'Rusak',
            self::Hilang => 'Hilang',
            self::PakaiInternal => 'Pakai Internal',
            self::ReturSupplier => 'Retur ke Supplier',
            self::Lainnya => 'Lainnya',
        };
    }

    /**
     * Alasan yang wajib disertai keterangan tambahan.
     */
    public function wajibCatatan(): bool
    {
        return $this === self::Lainnya;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $hasil = [];

        foreach (self::cases() as $satu) {
            $hasil[$satu->value] = $satu->label();
        }

        return $hasil;
    }
}
