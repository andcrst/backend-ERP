<?php

namespace App\Models;

use App\Enums\JenisPembayaran;
use App\Enums\StatusBarang;
use App\Enums\StatusPembayaran;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'nomor_struk',
    'id_pengguna',
    'id_pelanggan',
    'tanggal_transaksi',
    'total_pembayaran',
    'jenis_pembayaran',
    'status_pembayaran',
    'jumlah_bayar',
    'referensi_pembayaran',
    'kembalian',
    'biaya_angkut',
    'catatan',
])]
class Transaksi extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'transaksi';

    protected $primaryKey = 'id_transaksi';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'datetime',
            'total_pembayaran' => 'decimal:2',
            'jenis_pembayaran' => JenisPembayaran::class,
            'jumlah_bayar' => 'decimal:2',
            'kembalian' => 'decimal:2',
            'biaya_angkut' => 'integer',
        ];
    }

    /**
     * Status transaksi dihitung dari status barangnya (D-20a) — bukan kolom.
     * `BERJALAN` bila masih ada barang menunggu/gagal diantar.
     */
    public function statusPesanan(): string
    {
        $detail = $this->relationLoaded('detail') ? $this->detail : $this->detail()->get();

        $selesai = $detail->every(fn (DetailTransaksi $baris): bool => ! $baris->statusBarang()->menungguGudang());

        return $selesai ? 'SELESAI' : 'BERJALAN';
    }

    /**
     * Apakah transaksi punya minimal satu barang yang perlu diantar.
     */
    public function perluDiantar(): bool
    {
        $detail = $this->relationLoaded('detail') ? $this->detail : $this->detail()->get();

        return $detail->contains(fn (DetailTransaksi $baris): bool => $baris->statusBarang()->menungguGudang());
    }

    /**
     * Barang yang sudah keluar gudang (stok sudah dipotong).
     */
    public function totalBiayaAngkut(): int
    {
        return (int) $this->biaya_angkut;
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna', 'id');
    }

    /**
     * @return BelongsTo<Pelanggan, $this>
     */
    public function pelanggan(): BelongsTo
    {
        return $this->belongsTo(Pelanggan::class, 'id_pelanggan', 'id_pelanggan');
    }

    /**
     * @return HasMany<DetailTransaksi, $this>
     */
    public function detail(): HasMany
    {
        return $this->hasMany(DetailTransaksi::class, 'id_transaksi', 'id_transaksi');
    }

    /**
     * Barang yang stoknya sudah dipotong (status Diambil / Sudah Diantar).
     *
     * @return HasMany<DetailTransaksi, $this>
     */
    public function detailSudahKeluar(): HasMany
    {
        return $this->detail()->whereIn('status_barang', [
            StatusBarang::Diambil->value,
            StatusBarang::SudahDiantar->value,
        ]);
    }

    /**
     * Daftar tugas gudang: hanya transaksi yang punya barang perlu diantar (G-12).
     *
     * @param  Builder<Transaksi>  $query
     */
    public function scopePunyaBarangDiantar(Builder $query): void
    {
        $query->whereHas('detail', fn (Builder $q) => $q->whereIn('status_barang', [
            StatusBarang::MenungguDiantar->value,
            StatusBarang::GagalDiantar->value,
        ]));
    }

    /**
     * Apakah transaksi masih berjalan (ada barang belum tuntas).
     */
    public function sedangBerjalan(): bool
    {
        return $this->perluDiantar();
    }

    /**
     * Transaksi berstatus BELUM_LUNAS (DP/tempo).
     *
     * @param  Builder<Transaksi>  $query
     */
    public function scopeBelumLunas(Builder $query): void
    {
        $query->where('status_pembayaran', StatusPembayaran::BelumLunas->value);
    }

    /**
     * Filter inklusif berdasarkan tanggal (YYYY-MM-DD).
     *
     * @param  Builder<Transaksi>  $query
     */
    public function scopeBetweenDates(Builder $query, ?string $from, ?string $to): void
    {
        $query->when($from !== null && $from !== '', fn (Builder $builder) => $builder
            ->where('tanggal_transaksi', '>=', $from.' 00:00:00'))
            ->when($to !== null && $to !== '', fn (Builder $builder) => $builder
                ->where('tanggal_transaksi', '<=', $to.' 23:59:59'));
    }
}
