<?php

namespace App\Enums;

/**
 * Status dokumen gudang yang punya tahap draf → posting.
 *
 * Dokumen `DRAFT` belum menyentuh stok; `POSTED` sudah tercatat di
 * `mutasi_stok` dan tidak dapat diubah lagi.
 */
enum StatusDokumen: string
{
    case Draft = 'DRAFT';
    case Posted = 'POSTED';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Posted => 'Terposting',
        };
    }

    public function sudahDiposting(): bool
    {
        return $this === self::Posted;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
