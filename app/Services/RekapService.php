<?php

namespace App\Services;

use App\Enums\JenisPembayaran;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Agregasi laporan Finance (CP-4) — BACA SAJA.
 *
 * Aturan wajib:
 * - **Transaksi dibatalkan TIDAK dihitung** (soft delete → `deleted_at IS NULL`).
 * - Rentang memakai `tanggal_transaksi` (`>=` / `<=`), bukan `whereDate()`.
 * - HPP memakai `harga_modal_snapshot` (D-05=A). Baris tanpa modal **ditandai**
 *   lewat `baris_tanpa_modal` & `hpp_cakupan`, tidak dianggap 0 diam-diam.
 * - Finance tidak pernah menulis data transaksi.
 */
class RekapService
{
    /**
     * Query dasar transaksi non-void dalam rentang tanggal.
     *
     * Tanpa `withTrashed()`: transaksi yang dibatalkan memang harus dikecualikan.
     */
    private function transaksi(?string $dari, ?string $sampai): Builder
    {
        // Prefix `transaksi.` wajib: pemanggil bisa men-join `users` yang juga
        // punya `deleted_at` (BUG-007 — kolom ambigu → SQL 500).
        $query = DB::table('transaksi')->whereNull('transaksi.deleted_at');

        if ($dari !== null && $dari !== '') {
            $query->where('transaksi.tanggal_transaksi', '>=', $dari.' 00:00:00');
        }

        if ($sampai !== null && $sampai !== '') {
            $query->where('transaksi.tanggal_transaksi', '<=', $sampai.' 23:59:59');
        }

        return $query;
    }

    /**
     * Query detail transaksi non-void dalam rentang tanggal.
     */
    private function detail(?string $dari, ?string $sampai): Builder
    {
        return DB::table('detail_transaksi')
            ->join('transaksi', 'transaksi.id_transaksi', '=', 'detail_transaksi.id_transaksi')
            ->whereNull('transaksi.deleted_at')
            ->when($dari !== null && $dari !== '', fn (Builder $q) => $q->where('transaksi.tanggal_transaksi', '>=', $dari.' 00:00:00'))
            ->when($sampai !== null && $sampai !== '', fn (Builder $q) => $q->where('transaksi.tanggal_transaksi', '<=', $sampai.' 23:59:59'));
    }

    /**
     * Rekap penjualan harian.
     *
     * @return list<array<string, mixed>>
     */
    public function harian(string $dari, string $sampai): array
    {
        return $this->transaksi($dari, $sampai)
            ->selectRaw('DATE(tanggal_transaksi) AS tanggal')
            ->selectRaw('COUNT(*) AS jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(total_pembayaran), 0) AS omzet')
            ->selectRaw('COALESCE(SUM(kembalian), 0) AS kembalian')
            ->selectRaw('COALESCE(SUM(biaya_angkut), 0) AS biaya_angkut')
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis_pembayaran = 'TUNAI' THEN total_pembayaran ELSE 0 END), 0) AS tunai")
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis_pembayaran = 'QRIS' THEN total_pembayaran ELSE 0 END), 0) AS qris")
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis_pembayaran = 'TRANSFER' THEN total_pembayaran ELSE 0 END), 0) AS transfer")
            ->groupByRaw('DATE(tanggal_transaksi)')
            ->orderByRaw('DATE(tanggal_transaksi)')
            ->get()
            ->map(fn ($baris): array => [
                'tanggal' => (string) $baris->tanggal,
                'jumlah_transaksi' => (int) $baris->jumlah_transaksi,
                'omzet' => (int) $baris->omzet,
                'kembalian' => (int) $baris->kembalian,
                'biaya_angkut' => (int) $baris->biaya_angkut,
                'tunai' => (int) $baris->tunai,
                'qris' => (int) $baris->qris,
                'transfer' => (int) $baris->transfer,
            ])
            ->all();
    }

    /**
     * Rekap penjualan bulanan (YYYY-MM).
     *
     * @return list<array<string, mixed>>
     */
    public function bulanan(string $dari, string $sampai): array
    {
        return $this->transaksi($dari, $sampai)
            ->selectRaw("DATE_FORMAT(tanggal_transaksi, '%Y-%m') AS periode")
            ->selectRaw('COUNT(*) AS jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(total_pembayaran), 0) AS omzet')
            ->selectRaw('COALESCE(SUM(kembalian), 0) AS kembalian')
            ->groupByRaw("DATE_FORMAT(tanggal_transaksi, '%Y-%m')")
            ->orderByRaw("DATE_FORMAT(tanggal_transaksi, '%Y-%m')")
            ->get()
            ->map(fn ($baris): array => [
                'periode' => (string) $baris->periode,
                'jumlah_transaksi' => (int) $baris->jumlah_transaksi,
                'omzet' => (int) $baris->omzet,
                'kembalian' => (int) $baris->kembalian,
            ])
            ->all();
    }

    /**
     * Rekap pendapatan: tunai vs non-tunai + tunai bersih.
     *
     * @return array<string, mixed>
     */
    public function pendapatan(string $dari, string $sampai): array
    {
        $baris = $this->transaksi($dari, $sampai)
            ->selectRaw('COUNT(*) AS jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(total_pembayaran), 0) AS omzet')
            ->selectRaw('COALESCE(SUM(kembalian), 0) AS kembalian')
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis_pembayaran = 'TUNAI' THEN total_pembayaran ELSE 0 END), 0) AS tunai")
            ->selectRaw("COALESCE(SUM(CASE WHEN jenis_pembayaran <> 'TUNAI' THEN total_pembayaran ELSE 0 END), 0) AS non_tunai")
            ->first();

        $tunai = (int) ($baris->tunai ?? 0);
        $kembalian = (int) ($baris->kembalian ?? 0);

        return [
            'jumlah_transaksi' => (int) ($baris->jumlah_transaksi ?? 0),
            'omzet' => (int) ($baris->omzet ?? 0),
            'tunai' => $tunai,
            'non_tunai' => (int) ($baris->non_tunai ?? 0),
            'kembalian' => $kembalian,
            'tunai_bersih' => $tunai - $kembalian,
        ];
    }

    /**
     * Rekap per metode pembayaran + persentase terhadap omzet.
     *
     * @return list<array<string, mixed>>
     */
    public function pembayaran(string $dari, string $sampai): array
    {
        $omzet = (int) $this->transaksi($dari, $sampai)->sum('total_pembayaran');

        return $this->transaksi($dari, $sampai)
            ->selectRaw('jenis_pembayaran')
            ->selectRaw('COUNT(*) AS jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(total_pembayaran), 0) AS total')
            ->groupBy('jenis_pembayaran')
            ->orderByDesc('total')
            ->get()
            ->map(function ($baris) use ($omzet): array {
                $total = (int) $baris->total;
                $jenis = JenisPembayaran::tryFrom((string) $baris->jenis_pembayaran);

                return [
                    'jenis_pembayaran' => (string) $baris->jenis_pembayaran,
                    'jenis_pembayaran_label' => $jenis?->label() ?? (string) $baris->jenis_pembayaran,
                    'jumlah_transaksi' => (int) $baris->jumlah_transaksi,
                    'total' => $total,
                    'persentase' => $omzet > 0 ? round($total / $omzet * 100, 2) : 0.0,
                ];
            })
            ->all();
    }

    /**
     * Rekap per kasir.
     *
     * @return list<array<string, mixed>>
     */
    public function kasir(string $dari, string $sampai): array
    {
        return $this->transaksi($dari, $sampai)
            ->join('users', 'users.id', '=', 'transaksi.id_pengguna')
            // `users` juga punya `deleted_at` → wajib diberi prefix agar tidak ambigu.
            ->whereNull('users.deleted_at')
            ->selectRaw('users.id AS id_pengguna, users.name AS nama_pengguna')
            ->selectRaw('COUNT(*) AS jumlah_transaksi')
            ->selectRaw('COALESCE(SUM(transaksi.total_pembayaran), 0) AS omzet')
            ->groupBy('users.id', 'users.name')
            ->orderByDesc('omzet')
            ->get()
            ->map(fn ($baris): array => [
                'id_pengguna' => (int) $baris->id_pengguna,
                'nama_pengguna' => (string) $baris->nama_pengguna,
                'jumlah_transaksi' => (int) $baris->jumlah_transaksi,
                'omzet' => (int) $baris->omzet,
                'rata_rata' => $baris->jumlah_transaksi > 0
                    ? (int) round($baris->omzet / $baris->jumlah_transaksi)
                    : 0,
            ])
            ->all();
    }

    /**
     * Rekap produk terlaris beserta HPP & laba kotor per produk.
     *
     * @return list<array<string, mixed>>
     */
    public function produk(string $dari, string $sampai, int $batas = 50): array
    {
        return $this->detail($dari, $sampai)
            ->selectRaw('detail_transaksi.id_produk')
            ->selectRaw('MAX(detail_transaksi.nama_produk_snapshot) AS nama_produk')
            ->selectRaw('COALESCE(SUM(detail_transaksi.jumlah), 0) AS jumlah_terjual')
            ->selectRaw('COALESCE(SUM(detail_transaksi.sub_total), 0) AS omzet')
            ->selectRaw('COALESCE(SUM(detail_transaksi.harga_modal_snapshot * detail_transaksi.jumlah), 0) AS hpp')
            ->selectRaw('SUM(CASE WHEN detail_transaksi.harga_modal_snapshot IS NULL THEN 1 ELSE 0 END) AS baris_tanpa_modal')
            ->groupBy('detail_transaksi.id_produk')
            ->orderByDesc('omzet')
            ->limit($batas)
            ->get()
            ->map(fn ($baris): array => [
                'id_produk' => (int) $baris->id_produk,
                'nama_produk' => (string) $baris->nama_produk,
                'jumlah_terjual' => (int) $baris->jumlah_terjual,
                'omzet' => (int) $baris->omzet,
                'hpp' => (int) $baris->hpp,
                'laba_kotor' => (int) $baris->omzet - (int) $baris->hpp,
                'baris_tanpa_modal' => (int) $baris->baris_tanpa_modal,
            ])
            ->all();
    }

    /**
     * Rekap laba kotor / HPP (D-05=A).
     *
     * `harga_modal_snapshot` NULL **tidak dihitung 0 dan tidak dibuang diam-diam**:
     * respons menyertakan `baris_tanpa_modal`, `hpp_cakupan`, dan `laba_kotor_estimasi`.
     *
     * @return array<string, mixed>
     */
    public function labaKotor(string $dari, string $sampai): array
    {
        $baris = $this->detail($dari, $sampai)
            ->selectRaw('COUNT(*) AS total_baris')
            ->selectRaw('COALESCE(SUM(detail_transaksi.jumlah), 0) AS item_terjual')
            ->selectRaw('COALESCE(SUM(detail_transaksi.sub_total), 0) AS pendapatan')
            ->selectRaw('SUM(CASE WHEN detail_transaksi.harga_modal_snapshot IS NULL THEN 1 ELSE 0 END) AS baris_tanpa_modal')
            ->selectRaw('COALESCE(SUM(CASE WHEN detail_transaksi.harga_modal_snapshot IS NOT NULL THEN detail_transaksi.sub_total ELSE 0 END), 0) AS pendapatan_bermodal')
            ->selectRaw('COALESCE(SUM(detail_transaksi.harga_modal_snapshot * detail_transaksi.jumlah), 0) AS hpp')
            ->first();

        $totalBaris = (int) ($baris->total_baris ?? 0);
        $tanpaModal = (int) ($baris->baris_tanpa_modal ?? 0);
        $pendapatan = (int) ($baris->pendapatan ?? 0);
        $pendapatanBermodal = (int) ($baris->pendapatan_bermodal ?? 0);
        $hpp = (int) ($baris->hpp ?? 0);

        $bermodal = $totalBaris - $tanpaModal;
        $labaKotor = $pendapatanBermodal - $hpp;

        return [
            'total_baris' => $totalBaris,
            'item_terjual' => (int) ($baris->item_terjual ?? 0),
            'pendapatan' => $pendapatan,
            'hpp' => $hpp,
            'laba_kotor' => $labaKotor,
            'margin_kotor' => $pendapatanBermodal > 0
                ? round($labaKotor / $pendapatanBermodal * 100, 2)
                : 0.0,
            'baris_tanpa_modal' => $tanpaModal,
            'hpp_cakupan' => $totalBaris > 0
                ? round($bermodal / $totalBaris * 100, 2)
                : 100.0,
            'hpp_lengkap' => $tanpaModal === 0,
            'catatan' => $tanpaModal === 0
                ? null
                : sprintf(
                    '%d dari %d baris belum punya harga modal; laba kotor hanya dihitung dari baris yang bermodal.',
                    $tanpaModal,
                    $totalBaris,
                ),
        ];
    }

    /**
     * Daftar transaksi non-void (detail per nota) dengan pagination.
     */
    public function daftarTransaksi(
        ?string $dari,
        ?string $sampai,
        ?int $idPengguna,
        ?string $jenis,
        int $perPage,
    ): LengthAwarePaginator {
        return DB::table('transaksi')
            ->whereNull('deleted_at')
            ->when($dari !== null && $dari !== '', fn (Builder $q) => $q->where('tanggal_transaksi', '>=', $dari.' 00:00:00'))
            ->when($sampai !== null && $sampai !== '', fn (Builder $q) => $q->where('tanggal_transaksi', '<=', $sampai.' 23:59:59'))
            ->when($idPengguna !== null, fn (Builder $q) => $q->where('id_pengguna', $idPengguna))
            ->when($jenis !== null && $jenis !== '', fn (Builder $q) => $q->where('jenis_pembayaran', $jenis))
            ->orderByDesc('tanggal_transaksi')
            ->orderByDesc('id_transaksi')
            ->paginate($perPage);
    }

    /**
     * Rekap stok & nilai persediaan.
     *
     * `nilai_persediaan_harga_jual` = Σ `harga` × `kuantitas`
     * `nilai_persediaan_modal`     = Σ `harga_modal` × `kuantitas`
     *
     * @return array<string, mixed>
     */
    public function stok(): array
    {
        $baris = DB::table('stok')
            ->selectRaw('COUNT(*) AS total_produk')
            ->selectRaw('COALESCE(SUM(kuantitas), 0) AS total_unit')
            ->selectRaw('COALESCE(SUM(harga * kuantitas), 0) AS nilai_harga_jual')
            ->selectRaw('COALESCE(SUM(CASE WHEN harga_modal IS NOT NULL THEN harga_modal * kuantitas ELSE 0 END), 0) AS nilai_modal')
            ->selectRaw('SUM(CASE WHEN harga_modal IS NULL THEN 1 ELSE 0 END) AS produk_tanpa_modal')
            ->selectRaw('SUM(CASE WHEN kuantitas <= stok_minimum THEN 1 ELSE 0 END) AS produk_menipis')
            ->selectRaw('SUM(CASE WHEN kuantitas <= 0 THEN 1 ELSE 0 END) AS produk_kosong')
            ->first();

        $totalProduk = (int) ($baris->total_produk ?? 0);
        $tanpaModal = (int) ($baris->produk_tanpa_modal ?? 0);
        $nilaiJual = (int) ($baris->nilai_harga_jual ?? 0);
        $nilaiModal = (int) ($baris->nilai_modal ?? 0);

        return [
            'total_produk' => $totalProduk,
            'total_unit' => (int) ($baris->total_unit ?? 0),
            'nilai_persediaan_harga_jual' => $nilaiJual,
            'nilai_persediaan_modal' => $nilaiModal,
            'potensi_laba' => $nilaiJual - $nilaiModal,
            'produk_menipis' => (int) ($baris->produk_menipis ?? 0),
            'produk_kosong' => (int) ($baris->produk_kosong ?? 0),
            'produk_tanpa_modal' => $tanpaModal,
            'modal_cakupan' => $totalProduk > 0
                ? round(($totalProduk - $tanpaModal) / $totalProduk * 100, 2)
                : 100.0,
        ];
    }
}
