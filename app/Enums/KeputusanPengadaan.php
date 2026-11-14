<?php

namespace App\Enums;

/**
 * Keputusan Finance/Owner atas pengajuan pengadaan.
 */
enum KeputusanPengadaan: string
{
    case Setujui = 'SETUJUI';
    case Tolak = 'TOLAK';
    case Revisi = 'REVISI';

    public function label(): string
    {
        return match ($this) {
            self::Setujui => 'Disetujui',
            self::Tolak => 'Ditolak',
            self::Revisi => 'Perlu Revisi',
        };
    }

    /**
     * Status pengadaan setelah keputusan ini diterapkan.
     */
    public function statusBaru(): StatusPengadaan
    {
        return match ($this) {
            self::Setujui => StatusPengadaan::Disetujui,
            self::Tolak => StatusPengadaan::Ditolak,
            self::Revisi => StatusPengadaan::Revisi,
        };
    }

    /**
     * Alasan wajib diisi untuk penolakan & permintaan revisi.
     */
    public function wajibAlasan(): bool
    {
        return $this !== self::Setujui;
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
