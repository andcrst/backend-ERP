<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * Pencatatan jejak audit aksi sensitif (AR9).
 *
 * Pemanggilan sebaiknya berada di dalam DB transaction yang sama dengan aksi
 * bisnisnya agar audit tidak tercatat bila aksi gagal.
 */
class AuditService
{
    /**
     * Nama aksi baku.
     */
    public const SALE_CREATE = 'SALE_CREATE';

    public const SALE_VOID = 'SALE_VOID';

    public const BARANG_STATUS_UBAH = 'BARANG_STATUS_UBAH';

    public const BIAYA_ANGKUT_UBAH = 'BIAYA_ANGKUT_UBAH';

    public const PENGAJUAN_PENGADAAN = 'PENGAJUAN_PENGADAAN';

    public const PENGADAAN_SETUJUI = 'PENGADAAN_SETUJUI';

    public const PENGADAAN_TOLAK = 'PENGADAAN_TOLAK';

    public const PENGADAAN_TUTUP = 'PENGADAAN_TUTUP';

    public const STOK_PENYESUAIAN = 'STOK_PENYESUAIAN';

    public const STOK_OPNAME_SETUJUI = 'STOK_OPNAME_SETUJUI';

    public const USER_UBAH_ROLE = 'USER_UBAH_ROLE';

    public const USER_NONAKTIF = 'USER_NONAKTIF';

    /**
     * Catat satu aksi.
     *
     * @param  array<string, mixed>|null  $sebelum
     * @param  array<string, mixed>|null  $sesudah
     */
    public function catat(
        string $aksi,
        ?Model $entitas = null,
        ?array $sebelum = null,
        ?array $sesudah = null,
        ?int $idPengguna = null,
    ): AuditLog {
        $request = $this->request();

        return AuditLog::create([
            'id_pengguna' => $idPengguna ?? auth()->id(),
            'aksi' => $aksi,
            'entitas_tipe' => $entitas !== null ? $entitas->getTable() : '-',
            'entitas_id' => $entitas?->getKey(),
            'data_sebelum' => $sebelum,
            'data_sesudah' => $sesudah,
            'ip_address' => $request?->ip(),
            'user_agent' => $request !== null
                ? substr((string) $request->userAgent(), 0, 255)
                : null,
        ]);
    }

    private function request(): ?Request
    {
        if (! app()->bound('request')) {
            return null;
        }

        $request = app('request');

        return $request instanceof Request ? $request : null;
    }
}
