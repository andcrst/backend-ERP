<?php

namespace App\Enums;

/**
 * Peran pengguna ERP — final 4 nilai (keputusan D-02=A).
 *
 * Nilai `MANAGER` sudah dihapus; akun lama dipetakan ke `OWNER`.
 */
enum UserRole: string
{
    case Owner = 'OWNER';
    case Finance = 'FINANCE';
    case Gudang = 'GUDANG';
    case Kasir = 'KASIR';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Finance => 'Finance',
            self::Gudang => 'Petugas Gudang',
            self::Kasir => 'Petugas Kasir',
        };
    }

    /**
     * Nama role Spatie yang setara nilai ENUM ini.
     */
    public function spatieRole(): string
    {
        return match ($this) {
            self::Owner => 'owner',
            self::Finance => 'finance',
            self::Gudang => 'warehouse',
            self::Kasir => 'cashier',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return list<string>
     */
    public static function spatieNames(): array
    {
        return array_map(fn (self $role): string => $role->spatieRole(), self::cases());
    }
}
