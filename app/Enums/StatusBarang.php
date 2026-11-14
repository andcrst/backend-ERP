<?php

namespace App\Enums;

/**
 * Status per BARANG dalam pesanan (keputusan D-20a).
 *
 * Hanya 4 status. Nilai `DIAMBIL_NANTI` dan `DISIAPKAN` sengaja TIDAK ada.
 */
enum StatusBarang: string
{
    case Diambil = 'DIAMBIL';
    case MenungguDiantar = 'MENUNGGU_DIANTAR';
    case SudahDiantar = 'SUDAH_DIANTAR';
    case GagalDiantar = 'GAGAL_DIANTAR';

    public function label(): string
    {
        return match ($this) {
            self::Diambil => 'Diambil',
            self::MenungguDiantar => 'Menunggu Diantar',
            self::SudahDiantar => 'Sudah Diantar',
            self::GagalDiantar => 'Gagal Diantar',
        };
    }

    /**
     * Apakah status ini menandakan barang sudah keluar dari gudang
     * (stok sudah/akan dipotong saat status ini tercapai).
     */
    public function mengurangiStok(): bool
    {
        return $this === self::Diambil || $this === self::SudahDiantar;
    }

    /**
     * Apakah barang masih menunggu proses pengantaran oleh gudang.
     */
    public function menungguGudang(): bool
    {
        return $this === self::MenungguDiantar || $this === self::GagalDiantar;
    }

    /**
     * Transisi yang diizinkan. `SUDAH_DIANTAR` dan `GAGAL_DIANTAR` bersifat final
     * (tidak dapat diubah lagi) agar jejak audit tetap utuh.
     *
     * @return list<self>
     */
    public function transisiDiizinkan(): array
    {
        return match ($this) {
            self::MenungguDiantar => [self::SudahDiantar, self::GagalDiantar],
            self::GagalDiantar => [self::SudahDiantar],
            self::Diambil, self::SudahDiantar => [],
        };
    }

    public function bisaBerubahKe(self $tujuan): bool
    {
        return in_array($tujuan, $this->transisiDiizinkan(), true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
