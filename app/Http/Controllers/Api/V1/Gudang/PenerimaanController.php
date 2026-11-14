<?php

namespace App\Http\Controllers\Api\V1\Gudang;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePenerimaanRequest;
use App\Http\Resources\PenerimaanResource;
use App\Models\Penerimaan;
use App\Models\Pengadaan;
use App\Services\PenerimaanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Penerimaan barang dari supplier (G-02) — selalu terikat pengadaan DISETUJUI.
 */
class PenerimaanController extends Controller
{
    public function __construct(private readonly PenerimaanService $penerimaan) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = Penerimaan::query()
            ->with('pengadaan:id_pengadaan,nomor_pengadaan,status', 'supplier:id_supplier,nama_supplier', 'pengguna:id,name')
            ->withCount('item')
            ->betweenDates($request->query('dari'), $request->query('sampai'))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->when($request->query('id_pengadaan'), fn ($q, $id) => $q->where('id_pengadaan', $id))
            ->orderByDesc('tanggal_penerimaan')
            ->orderByDesc('id_penerimaan')
            ->paginate($perPage)
            ->through(fn (Penerimaan $item) => new PenerimaanResource($item));

        return ApiResponse::paginated($data, 'Daftar penerimaan barang.');
    }

    /**
     * Buat dokumen penerimaan (draf) untuk pengadaan yang sudah disetujui.
     */
    public function store(StorePenerimaanRequest $request, Pengadaan $pengadaan): JsonResponse
    {
        $penerimaan = $this->penerimaan->buat($pengadaan, $request->validated());

        return ApiResponse::success(
            new PenerimaanResource($penerimaan->load('item.produk', 'pengadaan', 'supplier')),
            'Dokumen penerimaan berhasil dibuat (draf).',
            201,
        );
    }

    public function show(Penerimaan $penerimaan): JsonResponse
    {
        return ApiResponse::success(
            new PenerimaanResource($penerimaan->load('item.produk', 'pengadaan', 'supplier', 'pengguna')),
        );
    }

    /**
     * Posting — stok bertambah & `harga_modal` produk diperbarui.
     */
    public function posting(Penerimaan $penerimaan): JsonResponse
    {
        $hasil = $this->penerimaan->posting($penerimaan);

        return ApiResponse::success(
            new PenerimaanResource($hasil),
            'Penerimaan berhasil diposting — stok bertambah dan harga modal diperbarui.',
        );
    }
}
