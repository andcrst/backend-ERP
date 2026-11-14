<?php

namespace App\Enums;

/**
 * Daur hidup pengadaan 4 tahap (D-19).
 *
 * GUDANG mengajukan; FINANCE/OWNER memutuskan (setujui/tolak/revisi);
 * GUDANG menerima barang (posting → stok bertambah); FINANCE/OWNER menutup.
 *
 * Gudang TIDAK boleh menyetujui/menutup pengadaannya sendiri (P5/P8).
 */
enum StatusPengadaan: string
{
    case Diajukan = 'DIAJUKAN';
    case Disetujui = 'DISETUJUI';
    case Ditolak = 'DITOLAK';
    case Revisi = 'REVISI';
    case Diterima = 'DITERIMA';
    case Ditutup = 'DITUTUP';

    public function label(): string
    {
        return match ($this) {
            self::Diajukan => 'Diajukan',
            self::Disetujui => 'Disetujui',
            self::Ditolak => 'Ditolak',
            self::Revisi => 'Perlu Revisi',
            self::Diterima => 'Diterima',
            self::Ditutup => 'Ditutup',
        };
    }

    /**
     * Boleh diputuskan Finance/Owner.
     */
    public function dapatDiputuskan(): bool
    {
        return $this === self::Diajukan;
    }

    /**
     * Boleh diajukan ulang oleh gudang setelah revisi/ditolak.
     */
    public function dapatDiajukanUlang(): bool
    {
        return $this === self::Revisi || $this === self::Ditolak;
    }

    /**
     * Boleh dibuatkan penerimaan barang.
     */
    public function dapatDiterima(): bool
    {
        return $this === self::Disetujui;
    }

    /**
     * Boleh ditutup Finance/Owner.
     */
    public function dapatDitutup(): bool
    {
        return $this === self::Diterima;
    }

    /**
     * Status final — tidak berubah lagi.
     */
    public function final(): bool
    {
        return $this === self::Ditutup || $this === self::Ditolak;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
