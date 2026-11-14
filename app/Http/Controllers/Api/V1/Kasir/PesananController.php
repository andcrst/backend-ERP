<?php

namespace App\Http\Controllers\Api\V1\Kasir;

use App\Enums\StatusBarang;
use App\Http\Controllers\Controller;
use App\Http\Requests\StorePesananRequest;
use App\Http\Requests\UpdateBiayaAngkutRequest;
use App\Http\Requests\UpdateStatusBarangRequest;
use App\Http\Resources\DetailTransaksiResource;
use App\Http\Resources\TransaksiResource;
use App\Models\DetailTransaksi;
use App\Models\Transaksi;
use App\Services\PenjualanService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Pesanan kasir + konfirmasi pengantaran (CP-1).
 *
 * Status melekat pada BARANG (D-20a). Stok hanya berkurang untuk barang
 * `DIAMBIL` (saat simpan) dan `SUDAH_DIANTAR` (saat gudang konfirmasi).
 */
class PesananController extends Controller
{
    public function __construct(private readonly PenjualanService $penjualan) {}

    /**
     * Daftar pesanan.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = Transaksi::query()
            ->with('pengguna:id,name,email', 'pelanggan')
            ->withCount('detail')
            ->betweenDates($request->query('dari'), $request->query('sampai'))
            ->when($request->query('id_pengguna'), fn ($q, $id) => $q->where('id_pengguna', $id))
            ->when($request->query('jenis_pembayaran'), fn ($q, $jenis) => $q->where('jenis_pembayaran', $jenis))
            ->when($request->query('status_pembayaran'), fn ($q, $status) => $q->where('status_pembayaran', $status))
            ->when($request->boolean('berjalan'), fn ($q) => $q->punyaBarangDiantar())
            ->orderByDesc('tanggal_transaksi')
            ->paginate($perPage)
            ->through(fn (Transaksi $item) => new TransaksiResource($item));

        return ApiResponse::paginated($data, 'Daftar pesanan.');
    }

    /**
     * Buat pesanan (checkout).
     */
    public function store(StorePesananRequest $request): JsonResponse
    {
        $transaksi = $this->penjualan->buat($request->validated());

        return ApiResponse::success(
            new TransaksiResource($transaksi),
            'Pesanan berhasil dibuat.',
            201,
        );
    }

    public function show(Transaksi $pesanan): JsonResponse
    {
        return ApiResponse::success(
            new TransaksiResource($pesanan->load('detail.produk', 'pengguna', 'pelanggan')),
        );
    }

    /**
     * Data struk — status tampil per baris barang.
     */
    public function struk(Transaksi $pesanan): JsonResponse
    {
        return ApiResponse::success(
            new TransaksiResource($pesanan->load('detail.produk', 'pengguna', 'pelanggan')),
            'Data struk.',
        );
    }

    /**
     * Batalkan pesanan — KHUSUS OWNER (D-04a=A).
     */
    public function destroy(Request $request, Transaksi $pesanan): JsonResponse
    {
        $alasan = (string) $request->input('alasan', '');

        $this->penjualan->batalkan($pesanan, $alasan);

        return ApiResponse::success(null, 'Pesanan dibatalkan dan stok dikembalikan.');
    }

    /**
     * Daftar tugas gudang — HANYA transaksi yang punya barang perlu diantar (G-12).
     */
    public function tugasAntar(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = Transaksi::query()
            ->punyaBarangDiantar()
            ->with(['detail' => fn ($q) => $q->whereIn('status_barang', [
                StatusBarang::MenungguDiantar->value,
                StatusBarang::GagalDiantar->value,
            ]), 'detail.produk', 'pelanggan', 'pengguna:id,name,email'])
            ->orderBy('tanggal_transaksi')
            ->paginate($perPage)
            ->through(fn (Transaksi $item) => new TransaksiResource($item));

        return ApiResponse::paginated($data, 'Daftar tugas pengantaran.');
    }

    /**
     * Ubah status satu barang (Petugas Gudang).
     */
    public function ubahStatusBarang(
        UpdateStatusBarangRequest $request,
        Transaksi $pesanan,
        DetailTransaksi $barang,
    ): JsonResponse {
        $hasil = $this->penjualan->ubahStatusBarang(
            $pesanan,
            $barang,
            StatusBarang::from($request->validated('status_barang')),
            null,
            $request->validated('catatan'),
        );

        return ApiResponse::success(
            new DetailTransaksiResource($hasil->load('produk')),
            'Status barang berhasil diperbarui.',
        );
    }

    /**
     * Isi biaya angkut (Petugas Gudang). Ditanggung toko — di luar total tagihan.
     */
    public function ubahBiayaAngkut(UpdateBiayaAngkutRequest $request, Transaksi $pesanan): JsonResponse
    {
        $transaksi = $this->penjualan->isiBiayaAngkut(
            $pesanan,
            (int) $request->validated('biaya_angkut'),
        );

        return ApiResponse::success(
            new TransaksiResource($transaksi->load('detail.produk', 'pelanggan', 'pengguna')),
            'Biaya angkut berhasil disimpan (ditanggung toko).',
        );
    }
}
