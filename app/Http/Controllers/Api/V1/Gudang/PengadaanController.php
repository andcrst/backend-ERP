<?php

namespace App\Http\Controllers\Api\V1\Gudang;

use App\Enums\StatusPengadaan;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePengadaanRequest;
use App\Http\Resources\PengadaanResource;
use App\Models\Pengadaan;
use App\Services\PengadaanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pengadaan tahap pengajuan (GUDANG) — D-19.
 *
 * Gudang hanya mengajukan; keputusan & penutupan ada di modul Finance.
 */
class PengadaanController extends Controller
{
    public function __construct(private readonly PengadaanService $pengadaan) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = Pengadaan::query()
            ->with('pengaju:id,name', 'penyetuju:id,name', 'supplier:id_supplier,nama_supplier')
            ->withCount('item')
            ->betweenDates($request->query('dari'), $request->query('sampai'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('id_supplier'), fn ($q, $id) => $q->where('id_supplier', $id))
            ->orderByDesc('tanggal_pengadaan')
            ->orderByDesc('id_pengadaan')
            ->paginate($perPage)
            ->through(fn (Pengadaan $item) => new PengadaanResource($item));

        return ApiResponse::paginated($data, 'Daftar pengadaan.');
    }

    /**
     * Ajukan pengadaan baru (G-01).
     */
    public function store(StorePengadaanRequest $request): JsonResponse
    {
        $pengadaan = $this->pengadaan->ajukan($request->validated());

        return ApiResponse::success(
            new PengadaanResource($pengadaan->load('item.produk', 'pengaju', 'supplier')),
            'Pengajuan pengadaan berhasil dibuat.',
            201,
        );
    }

    public function show(Pengadaan $pengadaan): JsonResponse
    {
        return ApiResponse::success(
            new PengadaanResource($pengadaan->load(
                'item.produk', 'pengaju', 'penyetuju', 'supplier', 'penerimaan',
            )),
        );
    }

    /**
     * Ajukan ulang setelah REVISI/DITOLAK (G-02a).
     */
    public function ajukanUlang(StorePengadaanRequest $request, Pengadaan $pengadaan): JsonResponse
    {
        $hasil = $this->pengadaan->ajukanUlang($pengadaan, $request->validated());

        return ApiResponse::success(
            new PengadaanResource($hasil),
            'Pengajuan pengadaan berhasil diperbarui dan diajukan ulang.',
        );
    }

    /**
     * Sisa yang belum diterima per produk (G-02, penerimaan bertahap).
     */
    public function sisa(Pengadaan $pengadaan): JsonResponse
    {
        $pengadaan->load('item.produk');

        return ApiResponse::success([
            'id_pengadaan' => $pengadaan->id_pengadaan,
            'nomor_pengadaan' => $pengadaan->nomor_pengadaan,
            'status' => $pengadaan->status->value,
            'lengkap_diterima' => $pengadaan->lengkapDiterima(),
            'item' => $pengadaan->item->map(fn ($satu): array => [
                'id_produk' => (int) $satu->id_produk,
                'nama_produk' => $satu->produk?->nama_produk,
                'satuan' => $satu->produk?->satuan,
                'jumlah_diminta' => (int) $satu->jumlah_diminta,
                'jumlah_diterima' => (int) $satu->jumlah_diterima,
                'sisa' => $satu->sisa(),
            ])->all(),
        ], 'Sisa pengadaan.');
    }

    /**
     * Opsi status pengadaan untuk filter frontend.
     */
    public function opsiStatus(): JsonResponse
    {
        $hasil = [];

        foreach (StatusPengadaan::cases() as $status) {
            $hasil[$status->value] = $status->label();
        }

        return ApiResponse::success($hasil, 'Opsi status pengadaan.');
    }
}
