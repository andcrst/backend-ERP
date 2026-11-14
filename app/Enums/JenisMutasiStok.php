<?php

namespace App\Enums;

/**
 * Jenis pergerakan stok — satu-satunya jalur perubahan `stok.kuantitas` (P1/AR2).
 */
enum JenisMutasiStok: string
{
    case SaldoAwal = 'SALDO_AWAL';
    case Penjualan = 'PENJUALAN';
    case PembatalanPenjualan = 'PEMBATALAN_PENJUALAN';
    case ReturPenjualan = 'RETUR_PENJUALAN';
    case Penerimaan = 'PENERIMAAN';
    case Pengeluaran = 'PENGELUARAN';
    case Penyesuaian = 'PENYESUAIAN';
    case Opname = 'OPNAME';

    public function label(): string
    {
        return match ($this) {
            self::SaldoAwal => 'Saldo Awal',
            self::Penjualan => 'Penjualan',
            self::PembatalanPenjualan => 'Pembatalan Penjualan',
            self::ReturPenjualan => 'Retur Penjualan',
            self::Penerimaan => 'Penerimaan Barang',
            self::Pengeluaran => 'Pengeluaran Barang',
            self::Penyesuaian => 'Penyesuaian',
            self::Opname => 'Opname',
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
