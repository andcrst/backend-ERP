<?php

namespace App\Services;

use App\Enums\JenisMutasiStok;
use App\Enums\StatusOpname;
use App\Exceptions\ApiException;
use App\Models\Opname;
use App\Models\OpnameItem;
use App\Models\Stok;
use Illuminate\Support\Facades\DB;

/**
 * Sesi opname stok dua tahap (G-06, D-04b=B).
 *
 * Tahap 1 — GUDANG: buat sesi (snapshot `kuantitas_sistem`), isi hasil hitung fisik.
 * Tahap 2 — OWNER/FINANCE: setujui → selisih diposting ke `mutasi_stok` jenis `OPNAME`.
 *
 * Gudang TIDAK boleh menyetujui hasil hitungnya sendiri (pemisahan tugas).
 */
class OpnameService
{
    public function __construct(
        private readonly StokMutasiService $mutasi,
        private readonly NomorDokumenService $nomor,
        private readonly AuditService $audit,
    ) {}

    /**
     * Buat sesi opname + snapshot kuantitas sistem.
     *
     * @param  array{id_kategori?:int|null, id_produk?:list<int>|null, catatan?:string|null, tanggal_opname?:string|null}  $data
     */
    public function buat(array $data, ?int $idPengguna = null): Opname
    {
        return DB::transaction(function () use ($data, $idPengguna): Opname {
            $query = Stok::query();

            if (! empty($data['id_kategori'])) {
                $query->where('id_kategori', (int) $data['id_kategori']);
            }

            if (! empty($data['id_produk'])) {
                $query->whereIn('id_produk', array_map('intval', $data['id_produk']));
            }

            $produk = $query->orderBy('id_produk')->get();

            if ($produk->isEmpty()) {
                throw new ApiException(
                    'Tidak ada produk yang masuk cakupan opname.',
                    'VALIDATION_ERROR',
                    422,
                    ['id_kategori' => ['Cakupan opname tidak menghasilkan produk.']],
                );
            }

            $opname = Opname::create([
                'nomor_opname' => $this->nomor->opname(),
                'tanggal_opname' => $data['tanggal_opname'] ?? now()->toDateString(),
                'status' => StatusOpname::Draft->value,
                'id_pengguna' => $idPengguna ?? (int) auth()->id(),
                'catatan' => $data['catatan'] ?? null,
            ]);

            foreach ($produk as $satu) {
                $opname->item()->create([
                    'id_produk' => $satu->id_produk,
                    'kuantitas_sistem' => (int) $satu->kuantitas,
                    'kuantitas_fisik' => null,
                    'selisih' => 0,
                ]);
            }

            return $opname->load('item.produk', 'pembuat');
        });
    }

    /**
     * Isi hasil hitung fisik satu baris (GUDANG). Selisih dihitung otomatis.
     */
    public function hitungItem(Opname $opname, OpnameItem $item, int $kuantitasFisik): OpnameItem
    {
        if (! $opname->status->masihDapatDihitung()) {
            throw new ApiException(
                'Hasil hitung tidak dapat diubah — sesi opname sudah '.$opname->status->label().'.',
                'VALIDATION_ERROR',
                422,
                ['status' => ['Sesi opname tidak lagi berstatus draf.']],
            );
        }

        if ((int) $item->id_opname !== (int) $opname->id_opname) {
            throw new ApiException('Barang tidak termasuk sesi opname ini.', 'NOT_FOUND', 404);
        }

        return DB::transaction(function () use ($opname, $item, $kuantitasFisik): OpnameItem {
            $item->forceFill([
                'kuantitas_fisik' => $kuantitasFisik,
                'selisih' => $kuantitasFisik - (int) $item->kuantitas_sistem,
            ])->save();

            // Sesi otomatis dianggap selesai dihitung bila semua baris terisi.
            if ($opname->status === StatusOpname::Draft && $opname->semuaSudahDihitung()) {
                $opname->forceFill([
                    'status' => StatusOpname::Dihitung->value,
                    'dihitung_at' => now(),
                ])->save();
            }

            return $item->refresh()->load('produk');
        });
    }

    /**
     * Setujui sesi opname (OWNER/FINANCE) — posting selisih ke stok.
     */
    public function setujui(Opname $opname, ?int $idPenyetuju = null): Opname
    {
        if ($opname->status->final()) {
            throw new ApiException(
                'Sesi opname sudah berstatus '.$opname->status->label().'.',
                'VALIDATION_ERROR',
                422,
                ['status' => ['Sesi opname sudah final.']],
            );
        }

        if (! $opname->status->dapatDiajukan()) {
            throw new ApiException(
                'Sesi opname belum selesai dihitung fisik.',
                'VALIDATION_ERROR',
                422,
                ['status' => ['Seluruh barang harus dihitung terlebih dahulu.']],
            );
        }

        // Pemisahan tugas: pembuat tidak boleh menyetujui (D-04b=B).
        $penyetuju = $idPenyetuju ?? (int) auth()->id();

        if ((int) $opname->id_pengguna === $penyetuju) {
            throw new ApiException(
                'Pembuat opname tidak boleh menyetujui hasil hitungnya sendiri.',
                'FORBIDDEN',
                403,
            );
        }

        return DB::transaction(function () use ($opname, $penyetuju): Opname {
            $item = $opname->item()->where('selisih', '<>', 0)->get();

            if ($item->isNotEmpty()) {
                $mutasi = $item->map(fn (OpnameItem $satu): array => [
                    'id_produk' => (int) $satu->id_produk,
                    'jumlah' => (int) $satu->selisih,
                    'jenis' => JenisMutasiStok::Opname,
                    'referensi_tipe' => 'opname',
                    'referensi_id' => $opname->id_opname,
                    'catatan' => sprintf(
                        '%s — sistem %d, fisik %d',
                        $opname->nomor_opname,
                        (int) $satu->kuantitas_sistem,
                        (int) $satu->kuantitas_fisik,
                    ),
                ])->all();

                $this->mutasi->catatBanyak($mutasi, $penyetuju);
            }

            $sebelum = ['status' => $opname->status->value];

            $opname->forceFill([
                'status' => StatusOpname::Disetujui->value,
                'id_penyetuju' => $penyetuju,
                'disetujui_at' => now(),
            ])->save();

            $this->audit->catat(
                AuditService::STOK_OPNAME_SETUJUI,
                $opname,
                $sebelum,
                [
                    'status' => StatusOpname::Disetujui->value,
                    'ringkasan' => $opname->ringkasanSelisih(),
                ],
                $penyetuju,
            );

            return $opname->refresh()->load('item.produk', 'pembuat', 'penyetuju');
        });
    }

    /**
     * Batalkan sesi opname (OWNER) — tidak menyentuh stok.
     */
    public function batalkan(Opname $opname, string $alasan, ?int $idPengguna = null): Opname
    {
        if ($opname->status->final()) {
            throw new ApiException(
                'Sesi opname sudah berstatus '.$opname->status->label().'.',
                'VALIDATION_ERROR',
                422,
                ['status' => ['Sesi opname sudah final.']],
            );
        }

        $sebelum = ['status' => $opname->status->value];

        $opname->forceFill([
            'status' => StatusOpname::Dibatalkan->value,
            'catatan' => trim(($opname->catatan ?? '')."\nDibatalkan: ".$alasan),
        ])->save();

        $this->audit->catat(
            AuditService::STOK_OPNAME_SETUJUI,
            $opname,
            $sebelum,
            ['status' => StatusOpname::Dibatalkan->value, 'alasan' => $alasan],
            $idPengguna,
        );

        return $opname->refresh()->load('item.produk', 'pembuat');
    }
}
