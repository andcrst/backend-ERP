<?php

namespace App\Http\Controllers\Api\V1\Gudang;

use App\Enums\StatusOpname;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOpnameRequest;
use App\Http\Requests\UpdateOpnameItemRequest;
use App\Http\Resources\OpnameItemResource;
use App\Http\Resources\OpnameResource;
use App\Models\Opname;
use App\Models\OpnameItem;
use App\Services\OpnameService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Sesi opname stok dua tahap (G-06).
 *
 * GUDANG membuat & menghitung; persetujuan WAJIB OWNER/FINANCE (D-04b=B).
 */
class OpnameController extends Controller
{
    public function __construct(private readonly OpnameService $opname) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = Opname::query()
            ->with('pembuat:id,name,email', 'penyetuju:id,name,email')
            ->withCount('item')
            ->betweenDates($request->query('dari'), $request->query('sampai'))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('tanggal_opname')
            ->orderByDesc('id_opname')
            ->paginate($perPage)
            ->through(fn (Opname $item) => new OpnameResource($item));

        return ApiResponse::paginated($data, 'Daftar sesi opname.');
    }

    /**
     * Buat sesi opname + snapshot kuantitas sistem.
     */
    public function store(StoreOpnameRequest $request): JsonResponse
    {
        $opname = $this->opname->buat($request->validated());

        return ApiResponse::success(
            new OpnameResource($opname->load('item.produk', 'pembuat')),
            'Sesi opname berhasil dibuat.',
            201,
        );
    }

    public function show(Opname $opname): JsonResponse
    {
        return ApiResponse::success(
            new OpnameResource($opname->load('item.produk', 'pembuat', 'penyetuju')),
        );
    }

    /**
     * Isi hasil hitung fisik satu barang (GUDANG).
     */
    public function hitungItem(
        UpdateOpnameItemRequest $request,
        Opname $opname,
        OpnameItem $item,
    ): JsonResponse {
        $hasil = $this->opname->hitungItem(
            $opname,
            $item,
            (int) $request->validated('kuantitas_fisik'),
        );

        return ApiResponse::success(
            new OpnameItemResource($hasil->load('produk')),
            'Hasil hitung fisik berhasil disimpan.',
        );
    }

    /**
     * Setujui sesi opname — OWNER/FINANCE (D-04b=B). Selisih diposting ke stok.
     */
    public function setujui(Opname $opname): JsonResponse
    {
        $hasil = $this->opname->setujui($opname);

        return ApiResponse::success(
            new OpnameResource($hasil),
            'Sesi opname disetujui dan stok telah disesuaikan.',
        );
    }

    /**
     * Batalkan sesi opname — OWNER. Tidak menyentuh stok.
     */
    public function batalkan(Request $request, Opname $opname): JsonResponse
    {
        $alasan = trim((string) $request->input('alasan', ''));

        if ($alasan === '') {
            return ApiResponse::error('Alasan pembatalan wajib diisi.', 'VALIDATION_ERROR', 422, [
                'alasan' => ['Alasan pembatalan wajib diisi.'],
            ]);
        }

        $hasil = $this->opname->batalkan($opname, $alasan);

        return ApiResponse::success(
            new OpnameResource($hasil),
            'Sesi opname dibatalkan.',
        );
    }

    /**
     * Ringkasan status opname yang tersedia.
     */
    public function opsiStatus(): JsonResponse
    {
        $hasil = [];

        foreach (StatusOpname::cases() as $status) {
            $hasil[$status->value] = $status->label();
        }

        return ApiResponse::success($hasil, 'Opsi status opname.');
    }
}
