<?php

namespace App\Http\Controllers\Api\V1\Finance;

use App\Http\Controllers\Controller;
use App\Services\RekapService;
use App\Support\ApiResponse;
use App\Support\CsvExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Rekap Finance (CP-4) — BACA SAJA.
 *
 * Semua endpoint menerima `dari`, `sampai`, dan `?format=csv` pada ekspor.
 * Transaksi dibatalkan otomatis dikecualikan (lihat RekapService).
 */
class RekapController extends Controller
{
    /**
     * Jenis rekap yang sah beserta labelnya.
     */
    private const JENIS = [
        'penjualan-harian' => 'Rekap penjualan harian',
        'penjualan-bulanan' => 'Rekap penjualan bulanan',
        'pendapatan' => 'Rekap pendapatan',
        'pembayaran' => 'Rekap metode pembayaran',
        'kasir' => 'Rekap per kasir',
        'produk' => 'Rekap produk terlaris',
        'laba-kotor' => 'Rekap laba kotor',
        'transaksi' => 'Daftar transaksi',
        'stok' => 'Rekap stok & nilai persediaan',
    ];

    public function __construct(private readonly RekapService $rekap) {}

    /**
     * Daftar jenis rekap yang tersedia (untuk menu frontend).
     */
    public function jenis(): JsonResponse
    {
        return ApiResponse::success(self::JENIS, 'Jenis rekap Finance.');
    }

    public function harian(Request $request): JsonResponse
    {
        [$dari, $sampai] = $this->rentang($request);

        return ApiResponse::success([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'rows' => $this->rekap->harian($dari, $sampai),
        ], 'Rekap penjualan harian.');
    }

    public function bulanan(Request $request): JsonResponse
    {
        [$dari, $sampai] = $this->rentang($request);

        return ApiResponse::success([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'rows' => $this->rekap->bulanan($dari, $sampai),
        ], 'Rekap penjualan bulanan.');
    }

    public function pendapatan(Request $request): JsonResponse
    {
        [$dari, $sampai] = $this->rentang($request);

        return ApiResponse::success([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            ...$this->rekap->pendapatan($dari, $sampai),
        ], 'Rekap pendapatan.');
    }

    public function pembayaran(Request $request): JsonResponse
    {
        [$dari, $sampai] = $this->rentang($request);

        return ApiResponse::success([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'rows' => $this->rekap->pembayaran($dari, $sampai),
        ], 'Rekap metode pembayaran.');
    }

    public function kasir(Request $request): JsonResponse
    {
        [$dari, $sampai] = $this->rentang($request);

        return ApiResponse::success([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'rows' => $this->rekap->kasir($dari, $sampai),
        ], 'Rekap per kasir.');
    }

    public function produk(Request $request): JsonResponse
    {
        [$dari, $sampai] = $this->rentang($request);
        $batas = min((int) $request->query('batas', 50), 500);

        return ApiResponse::success([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            'rows' => $this->rekap->produk($dari, $sampai, $batas),
        ], 'Rekap produk terlaris.');
    }

    public function labaKotor(Request $request): JsonResponse
    {
        [$dari, $sampai] = $this->rentang($request);

        return ApiResponse::success([
            'periode' => ['dari' => $dari, 'sampai' => $sampai],
            ...$this->rekap->labaKotor($dari, $sampai),
        ], 'Rekap laba kotor.');
    }

    public function transaksi(Request $request): JsonResponse
    {
        [$dari, $sampai] = $this->rentang($request);
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = $this->rekap->daftarTransaksi(
            $dari,
            $sampai,
            $request->filled('id_pengguna') ? (int) $request->query('id_pengguna') : null,
            $request->query('jenis_pembayaran'),
            $perPage,
        );

        return ApiResponse::paginated($data, 'Daftar transaksi (non-void).');
    }

    public function stok(): JsonResponse
    {
        return ApiResponse::success($this->rekap->stok(), 'Rekap stok & nilai persediaan.');
    }

    /**
     * Ekspor CSV: `/finance/rekap/{jenis}/ekspor?format=csv&dari=&sampai=`.
     */
    public function ekspor(Request $request, string $jenis): JsonResponse|StreamedResponse
    {
        if (! array_key_exists($jenis, self::JENIS)) {
            return ApiResponse::error('Jenis rekap tidak dikenal.', 'NOT_FOUND', 404, [
                'jenis' => ['Jenis rekap tidak dikenal.'],
            ]);
        }

        $format = (string) $request->query('format', 'csv');

        if ($format !== 'csv') {
            return ApiResponse::error(
                'Format ekspor hanya mendukung csv.',
                'VALIDATION_ERROR',
                422,
                ['format' => ['Format hanya csv.']],
            );
        }

        [$dari, $sampai] = $this->rentang($request);

        $data = $this->ambilData($jenis, $dari, $sampai);

        $baris = array_map(
            fn ($satu): array => is_array($satu) ? $satu : (array) $satu,
            $data,
        );

        return CsvExporter::dariBaris(
            $baris,
            sprintf('rekap-%s-%s_%s.csv', $jenis, $dari, $sampai),
        );
    }

    /**
     * Ambil data rekap sesuai jenis (untuk ekspor).
     *
     * @return list<array<string, mixed>>
     */
    private function ambilData(string $jenis, string $dari, string $sampai): array
    {
        return match ($jenis) {
            'penjualan-harian' => $this->rekap->harian($dari, $sampai),
            'penjualan-bulanan' => $this->rekap->bulanan($dari, $sampai),
            'pendapatan' => [$this->rekap->pendapatan($dari, $sampai)],
            'pembayaran' => $this->rekap->pembayaran($dari, $sampai),
            'kasir' => $this->rekap->kasir($dari, $sampai),
            'produk' => $this->rekap->produk($dari, $sampai, 500),
            'laba-kotor' => [$this->rekap->labaKotor($dari, $sampai)],
            'stok' => [$this->rekap->stok()],
            'transaksi' => array_map(
                fn ($satu): array => (array) $satu,
                $this->rekap->daftarTransaksi($dari, $sampai, null, null, 100)->items(),
            ),
            default => [],
        };
    }

    /**
     * Rentang tanggal: default bulan berjalan.
     *
     * @return array{0:string, 1:string}
     */
    private function rentang(Request $request): array
    {
        $dari = (string) ($request->query('dari') ?? now()->startOfMonth()->toDateString());
        $sampai = (string) ($request->query('sampai') ?? now()->toDateString());

        return [$dari, $sampai];
    }
}
