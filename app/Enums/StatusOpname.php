<?php

namespace App\Enums;

/**
 * Daur hidup sesi opname stok (G-06).
 *
 * `DRAFT`      — sesi dibuat, snapshot `kuantitas_sistem` diambil; belum dihitung.
 * `DIHITUNG`   — seluruh item sudah diisi hasil hitung fisik; menunggu persetujuan.
 * `DISETUJUI`  — disetujui OWNER/FINANCE; selisih sudah diposting ke `mutasi_stok`.
 * `DIBATALKAN` — sesi dibatalkan pemilik; tidak menyentuh stok.
 *
 * Gudang hanya boleh sampai `DIHITUNG` — persetujuan wajib OWNER/FINANCE (D-04b=B).
 */
enum StatusOpname: string
{
    case Draft = 'DRAFT';
    case Dihitung = 'DIHITUNG';
    case Disetujui = 'DISETUJUI';
    case Dibatalkan = 'DIBATALKAN';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Dihitung => 'Dihitung',
            self::Disetujui => 'Disetujui',
            self::Dibatalkan => 'Dibatalkan',
        };
    }

    /**
     * Sesi yang masih boleh diisi hasil hitung fisik.
     */
    public function masihDapatDihitung(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Sesi yang boleh diajukan untuk persetujuan.
     */
    public function dapatDiajukan(): bool
    {
        return $this === self::Dihitung;
    }

    /**
     * Sesi final — tidak boleh diubah lagi.
     */
    public function final(): bool
    {
        return $this === self::Disetujui || $this === self::Dibatalkan;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
