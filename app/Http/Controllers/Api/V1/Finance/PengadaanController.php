<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Enums\KeputusanPengadaan;
use App\Enums\StatusPengadaan;
use App\Http\Controllers\Controller;
use App\Http\Requests\PutuskanPengadaanRequest;
use App\Http\Resources\PengadaanResource;
use App\Models\Pengadaan;
use App\Services\PengadaanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Keputusan & penutupan pengadaan (FINANCE / OWNER) — D-19.
 *
 * GUDANG tidak boleh menyetujui/menutup pengadaannya sendiri (P5/P8).
 */
class PengadaanController extends Controller
{
    public function __construct(private readonly PengadaanService $pengadaan) {}

    /**
     * Daftar pengadaan yang menunggu keputusan.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = Pengadaan::query()
            ->with('pengaju:id,name', 'penyetuju:id,name', 'supplier:id_supplier,nama_supplier')
            ->withCount('item')
            ->betweenDates($request->query('dari'), $request->query('sampai'))
            ->when(
                $request->query('status'),
                fn ($q, $s) => $q->where('status', $s),
                fn ($q) => $q->where('status', StatusPengadaan::Diajukan->value),
            )
            ->orderBy('tanggal_pengadaan')
            ->paginate($perPage)
            ->through(fn (Pengadaan $item) => new PengadaanResource($item));

        return ApiResponse::paginated($data, 'Daftar pengadaan.');
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
     * Setujui / tolak / minta revisi.
     */
    public function putuskan(PutuskanPengadaanRequest $request, Pengadaan $pengadaan): JsonResponse
    {
        $hasil = $this->pengadaan->putuskan(
            $pengadaan,
            KeputusanPengadaan::from($request->validated('keputusan')),
            $request->validated('catatan'),
        );

        return ApiResponse::success(
            new PengadaanResource($hasil),
            'Keputusan pengadaan tersimpan: '.$hasil->status->label().'.',
        );
    }

    /**
     * Tutup pengadaan — hanya setelah barang diterima.
     */
    public function tutup(Request $request, Pengadaan $pengadaan): JsonResponse
    {
        $hasil = $this->pengadaan->tutup(
            $pengadaan,
            $request->input('catatan'),
        );

        return ApiResponse::success(
            new PengadaanResource($hasil),
            'Pengadaan ditutup.',
        );
    }

    /**
     * Opsi keputusan untuk frontend.
     */
    public function opsiKeputusan(): JsonResponse
    {
        return ApiResponse::success(KeputusanPengadaan::options(), 'Opsi keputusan pengadaan.');
    }
}
