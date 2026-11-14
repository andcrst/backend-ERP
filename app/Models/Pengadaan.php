<?php

namespace App\Models;

use App\Enums\StatusPengadaan;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Pengadaan 4 tahap (D-19).
 *
 * Tidak pernah menyentuh stok sendiri — stok hanya berubah lewat posting
 * penerimaan (G-02).
 */
#[Fillable([
    'nomor_pengadaan',
    'tanggal_pengadaan',
    'tanggal_kebutuhan',
    'id_supplier',
    'id_pengaju',
    'id_penyetuju',
    'status',
    'alasan',
    'catatan_keputusan',
    'diputuskan_at',
    'diterima_at',
    'ditutup_at',
])]
class Pengadaan extends Model
{
    protected $table = 'pengadaan';

    protected $primaryKey = 'id_pengadaan';

    /**
     * Route model binding memakai PK kustom (bukan `id`).
     */
    public function getRouteKeyName(): string
    {
        return 'id_pengadaan';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_pengadaan' => 'date',
            'tanggal_kebutuhan' => 'date',
            'status' => StatusPengadaan::class,
            'diputuskan_at' => 'datetime',
            'diterima_at' => 'datetime',
            'ditutup_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PengadaanItem, $this>
     */
    public function item(): HasMany
    {
        return $this->hasMany(PengadaanItem::class, 'id_pengadaan', 'id_pengadaan');
    }

    /**
     * @return HasMany<Penerimaan, $this>
     */
    public function penerimaan(): HasMany
    {
        return $this->hasMany(Penerimaan::class, 'id_pengadaan', 'id_pengadaan');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengaju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengaju', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_penyetuju', 'id');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
    }

    /**
     * Sisa yang belum diterima per produk.
     *
     * @return array<int, array{diminta:int, diterima:int, sisa:int}>
     */
    public function sisaPerProduk(): array
    {
        return $this->item->mapWithKeys(fn (PengadaanItem $satu): array => [
            (int) $satu->id_produk => [
                'diminta' => (int) $satu->jumlah_diminta,
                'diterima' => (int) $satu->jumlah_diterima,
                'sisa' => max(0, (int) $satu->jumlah_diminta - (int) $satu->jumlah_diterima),
            ],
        ])->all();
    }

    /**
     * Apakah seluruh barang sudah diterima (siap ditutup).
     */
    public function lengkapDiterima(): bool
    {
        return $this->item()->whereColumn('jumlah_diterima', '<', 'jumlah_diminta')->doesntExist();
    }

    /**
     * @param  Builder<Pengadaan>  $query
     */
    public function scopeBetweenDates(Builder $query, ?string $dari, ?string $sampai): void
    {
        if ($dari !== null && $dari !== '') {
            $query->where('tanggal_pengadaan', '>=', $dari);
        }

        if ($sampai !== null && $sampai !== '') {
            $query->where('tanggal_pengadaan', '<=', $sampai);
        }
    }
}
