<?php

namespace App\Http\Controllers\Api\V1\Gudang;

use App\Enums\AlasanPengeluaran;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePengeluaranRequest;
use App\Http\Resources\PengeluaranResource;
use App\Models\Pengeluaran;
use App\Services\PengeluaranService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Barang keluar non-penjualan (G-05).
 *
 * Draf tidak menyentuh stok; posting mengurangi stok lewat PengeluaranService.
 */
class PengeluaranController extends Controller
{
    public function __construct(private readonly PengeluaranService $pengeluaran) {}

    /**
     * Daftar dokumen pengeluaran.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = Pengeluaran::query()
            ->with('pengguna:id,name,email')
            ->withCount('item')
            ->betweenDates($request->query('dari'), $request->query('sampai'))
            ->when($request->query('alasan'), fn ($q, $alasan) => $q->where('alasan', $alasan))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('tanggal_pengeluaran')
            ->orderByDesc('id_pengeluaran')
            ->paginate($perPage)
            ->through(fn (Pengeluaran $item) => new PengeluaranResource($item));

        return ApiResponse::paginated($data, 'Daftar pengeluaran barang.');
    }

    /**
     * Opsi alasan yang sah (untuk dropdown frontend).
     */
    public function opsiAlasan(): JsonResponse
    {
        return ApiResponse::success(AlasanPengeluaran::options(), 'Opsi alasan pengeluaran.');
    }

    /**
     * Buat dokumen pengeluaran (draf).
     */
    public function store(StorePengeluaranRequest $request): JsonResponse
    {
        $dokumen = $this->pengeluaran->buat($request->validated());

        return ApiResponse::success(
            new PengeluaranResource($dokumen->load('item.produk', 'pengguna')),
            'Dokumen pengeluaran berhasil dibuat (draf).',
            201,
        );
    }

    public function show(Pengeluaran $pengeluaran): JsonResponse
    {
        return ApiResponse::success(
            new PengeluaranResource($pengeluaran->load('item.produk', 'pengguna')),
        );
    }

    /**
     * Posting — stok berkurang dan tercatat di kartu stok.
     */
    public function posting(Pengeluaran $pengeluaran): JsonResponse
    {
        $dokumen = $this->pengeluaran->posting($pengeluaran);

        return ApiResponse::success(
            new PengeluaranResource($dokumen),
            'Pengeluaran berhasil diposting dan stok telah dikurangi.',
        );
    }
}
