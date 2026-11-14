<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTransaksiRequest;
use App\Http\Resources\TransaksiResource;
use App\Models\Transaksi;
use App\Services\NomorStrukService;
use App\Services\PenjualanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Endpoint lama `/transaksi` — dipertahankan sebagai ALIAS (T-05).
 *
 * Logika dipindahkan ke PenjualanService (CP-1). Semua barang default
 * berstatus `DIAMBIL` sehingga perilaku lama tetap sama.
 *
 * @deprecated Gunakan `/kasir/pesanan`.
 */
class TransaksiController extends Controller
{
    public function __construct(
        private readonly NomorStrukService $nomor,
        private readonly PenjualanService $penjualan,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $transaksi = Transaksi::query()
            ->with('pengguna:id,name,email', 'pelanggan')
            ->withCount('detail')
            ->betweenDates(
                $request->query('dari'),
                $request->query('sampai'),
            )
            ->when($request->query('id_pengguna'), fn ($query, $id) => $query
                ->where('id_pengguna', $id))
            ->when($request->query('jenis_pembayaran'), fn ($query, $jenis) => $query
                ->where('jenis_pembayaran', $jenis))
            ->orderByDesc('tanggal_transaksi')
            ->paginate($perPage)
            ->through(fn (Transaksi $item) => new TransaksiResource($item));

        return ApiResponse::paginated($transaksi, 'Daftar transaksi.');
    }

    public function store(StoreTransaksiRequest $request): JsonResponse
    {
        $data = $request->validated();

        // Alias lama: seluruh barang dianggap DIAMBIL.
        $data['detail'] = array_map(
            fn (array $baris): array => $baris + ['status_barang' => 'DIAMBIL'],
            $data['detail'],
        );

        $transaksi = $this->penjualan->buat($data);

        return ApiResponse::success(
            new TransaksiResource($transaksi),
            'Transaksi berhasil.',
            201,
        );
    }

    public function show(Transaksi $transaksi): JsonResponse
    {
        return ApiResponse::success(
            new TransaksiResource($transaksi->load('detail.produk', 'pengguna', 'pelanggan')),
        );
    }

    public function struk(Transaksi $transaksi): JsonResponse
    {
        return ApiResponse::success(
            new TransaksiResource($transaksi->load('detail.produk', 'pengguna', 'pelanggan')),
            'Data struk.',
        );
    }

    /**
     * Batalkan transaksi dan kembalikan stok. Baris di-soft delete agar
     * nomor struk tetap terpakai sebagai histori.
     */
    public function destroy(Request $request, Transaksi $transaksi): JsonResponse
    {
        $this->penjualan->batalkan(
            $transaksi,
            (string) $request->input('alasan', 'Dibatalkan melalui endpoint lama.'),
        );

        return ApiResponse::success(null, 'Transaksi dibatalkan dan stok dikembalikan.');
    }
}
