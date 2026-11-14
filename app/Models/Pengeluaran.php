<?php

namespace App\Models;

use App\Enums\AlasanPengeluaran;
use App\Enums\StatusDokumen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Barang keluar non-penjualan (G-05) — selalu beralasan.
 *
 * Draf belum menyentuh stok. Posting mengurangi stok lewat `StokMutasiService`
 * dengan jenis `PENGELUARAN`.
 */
#[Fillable([
    'nomor_pengeluaran',
    'tanggal_pengeluaran',
    'alasan',
    'id_pengguna',
    'status',
    'catatan',
    'posted_at',
])]
class Pengeluaran extends Model
{
    protected $table = 'pengeluaran';

    protected $primaryKey = 'id_pengeluaran';

    /**
     * Route model binding memakai PK kustom (bukan `id`).
     */
    public function getRouteKeyName(): string
    {
        return 'id_pengeluaran';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_pengeluaran' => 'date',
            'alasan' => AlasanPengeluaran::class,
            'status' => StatusDokumen::class,
            'posted_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PengeluaranItem, $this>
     */
    public function item(): HasMany
    {
        return $this->hasMany(PengeluaranItem::class, 'id_pengeluaran', 'id_pengeluaran');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna', 'id');
    }

    public function sudahDiposting(): bool
    {
        return $this->status === StatusDokumen::Posted;
    }

    /**
     * Total unit yang dikeluarkan per produk (bukan nilai uang — pengeluaran
     * bukan penjualan, jadi tidak ada harga).
     *
     * @return array<int, int>
     */
    public function jumlahPerProduk(): array
    {
        /** @var Collection<int, PengeluaranItem> $item */
        $item = $this->relationLoaded('item') ? $this->item : $this->item()->get();

        return $item->groupBy('id_produk')
            ->map(fn (Collection $baris): int => (int) $baris->sum('jumlah'))
            ->all();
    }

    /**
     * @param  Builder<Pengeluaran>  $query
     */
    public function scopeBetweenDates(Builder $query, ?string $dari, ?string $sampai): void
    {
        if ($dari !== null && $dari !== '') {
            $query->where('tanggal_pengeluaran', '>=', $dari);
        }

        if ($sampai !== null && $sampai !== '') {
            $query->where('tanggal_pengeluaran', '<=', $sampai);
        }
    }
}
