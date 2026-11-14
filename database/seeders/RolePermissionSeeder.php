<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role & permission ERP — 4 peran final (D-02=A).
 *
 * Pemetaan mengikuti docs/ERP_ACCESS_MATRIX.md v2.0.
 * Seeder bersifat idempoten (aman dijalankan berulang).
 */
class RolePermissionSeeder extends Seeder
{
    /**
     * Permission per modul.
     *
     * @var array<string, list<string>>
     */
    private array $permissions = [
        'user' => ['view', 'create', 'update', 'delete', 'ubah_role', 'reset_password'],
        'role' => ['view', 'update'],
        'audit' => ['view'],
        'pengaturan' => ['view', 'update'],
        'kategori' => ['view', 'create', 'update', 'delete'],
        'stok' => ['view', 'create', 'update', 'delete', 'mutasi', 'penyesuaian'],
        'stok.masuk' => ['view', 'create', 'posting'],
        'stok.keluar' => ['view', 'create', 'posting'],
        'stok.opname' => ['create', 'setujui'],
        'pengadaan' => ['view', 'create', 'setujui', 'tutup'],
        'supplier' => ['view', 'create', 'update', 'delete'],
        'pelanggan' => ['view', 'create', 'update', 'delete'],
        'transaksi' => ['view', 'create', 'void'],
        // `finance.rekonsiliasi` & `finance.tutup_buku` TIDAK dibuat (D-06 HOLD).
        'finance' => ['dashboard', 'rekap', 'ekspor'],
        'report' => ['view'],
    ];

    /**
     * Permission per peran (nama Spatie).
     *
     * @var array<string, list<string>|string>
     */
    private array $roles = [
        'owner' => '*',
        'finance' => [
            'stok.view', 'kategori.view',
            'pelanggan.view',
            'transaksi.view',
            'finance.dashboard', 'finance.rekap', 'finance.ekspor', 'report.view',
            'stok.masuk.view', 'stok.keluar.view', 'stok.mutasi',
            'stok.opname.setujui',
            // Pengadaan: Finance memutuskan & menutup (D-19).
            'pengadaan.view', 'pengadaan.setujui', 'pengadaan.tutup',
            'supplier.view',
        ],
        'warehouse' => [
            'stok.view', 'stok.create', 'stok.update', 'stok.delete',
            'stok.mutasi', 'stok.penyesuaian',
            'stok.masuk.view', 'stok.masuk.create', 'stok.masuk.posting',
            'stok.keluar.view', 'stok.keluar.create', 'stok.keluar.posting',
            'stok.opname.create',
            // Pengadaan: gudang mengajukan & menerima (D-19), TIDAK menyetujui.
            'pengadaan.view', 'pengadaan.create',
            'kategori.view', 'kategori.create', 'kategori.update', 'kategori.delete',
            'supplier.view', 'supplier.create', 'supplier.update', 'supplier.delete',
            // GUDANG TIDAK diberi `finance.rekap` — laba/HPP adalah data keuangan
            // (ERP_BUSINESS_PROCESS_BY_ROLE §3.4: gudang tidak melihat pendapatan/laba).
            'finance.dashboard', 'report.view',
        ],
        'cashier' => [
            'stok.view', 'kategori.view',
            'pelanggan.view', 'pelanggan.create', 'pelanggan.update',
            'transaksi.view', 'transaksi.create',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $semua = $this->buatPermission();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($this->roles as $namaRole => $daftar) {
            $role = Role::findOrCreate($namaRole, 'web');
            $role->syncPermissions($daftar === '*' ? $semua : $daftar);
        }

        // Role `manager` lama sudah tidak dipakai (D-02=A). Dihapus bila ada,
        // setelah dipastikan tidak ada pengguna yang masih memakainya.
        $manager = Role::query()->where('name', 'manager')->where('guard_name', 'web')->first();

        if ($manager !== null && $manager->users()->count() === 0) {
            $manager->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * @return list<string>
     */
    private function buatPermission(): array
    {
        $nama = [];

        foreach ($this->permissions as $modul => $aksi) {
            foreach ($aksi as $satu) {
                $nama[] = sprintf('%s.%s', $modul, $satu);
            }
        }

        $sudahAda = Permission::query()
            ->whereIn('name', $nama)
            ->where('guard_name', 'web')
            ->pluck('name')
            ->all();

        $hilang = array_values(array_diff($nama, $sudahAda));

        if ($hilang !== []) {
            $sekarang = now();

            Permission::insert(array_map(fn (string $satu): array => [
                'name' => $satu,
                'guard_name' => 'web',
                'created_at' => $sekarang,
                'updated_at' => $sekarang,
            ], $hilang));
        }

        return $nama;
    }

    /**
     * Role Spatie yang wajib ada (dipakai test).
     *
     * @return list<string>
     */
    public static function roleWajib(): array
    {
        return UserRole::spatieNames();
    }
}
