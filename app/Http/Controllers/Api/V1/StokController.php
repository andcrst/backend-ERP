<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\JenisMutasiStok;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStokRequest;
use App\Http\Resources\StokResource;
use App\Models\Stok;
use App\Services\NomorStrukService;
use App\Services\StokMutasiService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Master produk (tabel `stok`).
 *
 * D-09: `kuantitas` TIDAK diubah dari sini. Saat membuat produk baru, stok
 * awal (bila ada) dicatat sebagai mutasi `SALDO_AWAL` lewat StokMutasiService.
 */
class StokController extends Controller
{
    public function __construct(
        private readonly NomorStrukService $nomor,
        private readonly StokMutasiService $mutasi,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $stok = Stok::query()
            ->with('kategori:id_kategori,nama_kategori')
            ->search($request->query('search'))
            ->byKategori($request->filled('id_kategori') ? (int) $request->query('id_kategori') : null)
            ->when($request->boolean('tersedia'), fn ($query) => $query->withStock())
            ->when($request->boolean('menipis'), fn ($query) => $query->whereColumn('kuantitas', '<=', 'stok_minimum'))
            ->orderBy('nama_produk')
            ->paginate($perPage)
            ->through(fn (Stok $item) => new StokResource($item));

        return ApiResponse::paginated($stok, 'Daftar stok.');
    }

    public function store(StoreStokRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['kode_sku'] = $data['kode_sku'] ?? $this->nomor->generateSku($data['nama_produk']);
        $data['satuan'] = $data['satuan'] ?? 'unit';
        $data['stok_minimum'] = $data['stok_minimum'] ?? (int) config('erp.stok.minimum_default', 5);

        // Stok awal (opsional) hanya boleh diisi saat pembuatan produk baru.
        $stokAwal = (int) $request->input('kuantitas_awal', 0);
        $data['kuantitas'] = 0;

        $stok = DB::transaction(function () use ($data, $stokAwal): Stok {
            $produk = Stok::create($data);

            if ($stokAwal > 0) {
                $this->mutasi->catat(
                    $produk,
                    $stokAwal,
                    JenisMutasiStok::SaldoAwal,
                    'produk',
                    $produk->id_produk,
                    null,
                    'Stok awal saat produk dibuat',
                );
            }

            return $produk->refresh();
        });

        return ApiResponse::success(
            new StokResource($stok->load('kategori:id_kategori,nama_kategori')),
            'Produk stok berhasil dibuat.',
            201,
        );
    }

    public function show(Stok $stok): JsonResponse
    {
        return ApiResponse::success(
            new StokResource($stok->load('kategori:id_kategori,nama_kategori')),
        );
    }

    public function update(StoreStokRequest $request, Stok $stok): JsonResponse
    {
        // `kuantitas` sudah ditolak oleh FormRequest (prohibited) — D-09.
        $stok->fill($request->safe()->except('kuantitas', 'kuantitas_awal'))->save();

        return ApiResponse::success(
            new StokResource($stok->refresh()->load('kategori:id_kategori,nama_kategori')),
            'Stok berhasil diperbarui.',
        );
    }

    public function destroy(Stok $stok): JsonResponse
    {
        if ($stok->detailTransaksi()->exists()) {
            return ApiResponse::error(
                'Produk sudah dipakai pada transaksi sehingga tidak dapat dihapus.',
                'RESOURCE_IN_USE',
                409,
            );
        }

        $stok->delete();

        return ApiResponse::success(null, 'Produk stok berhasil dihapus.');
    }

    public function scan(Request $request): JsonResponse
    {
        $kode = trim((string) $request->query('kode_sku', ''));

        if ($kode === '') {
            return ApiResponse::error('Parameter kode_sku wajib diisi.', 'VALIDATION_ERROR', 422, [
                'kode_sku' => ['Parameter kode_sku wajib diisi.'],
            ]);
        }

        $stok = Stok::query()
            ->with('kategori:id_kategori,nama_kategori')
            ->where('kode_sku', $kode)
            ->first();

        if ($stok === null) {
            return ApiResponse::error('Produk tidak ditemukan.', 'NOT_FOUND', 404);
        }

        return ApiResponse::success(new StokResource($stok), 'Produk ditemukan.');
    }
}
