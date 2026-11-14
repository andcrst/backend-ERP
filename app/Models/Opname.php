<?php

namespace App\Models;

use App\Enums\StatusOpname;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Sesi opname stok (G-06).
 *
 * Gudang membuat & menghitung; persetujuan WAJIB OWNER/FINANCE (D-04b=B).
 * Setelah disetujui sesi bersifat final dan selisih sudah diposting ke stok.
 */
#[Fillable([
    'nomor_opname',
    'tanggal_opname',
    'status',
    'id_pengguna',
    'id_penyetuju',
    'dihitung_at',
    'disetujui_at',
    'catatan',
])]
class Opname extends Model
{
    protected $table = 'opname';

    protected $primaryKey = 'id_opname';

    /**
     * Route model binding memakai PK kustom (bukan `id`).
     */
    public function getRouteKeyName(): string
    {
        return 'id_opname';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_opname' => 'date',
            'status' => StatusOpname::class,
            'dihitung_at' => 'datetime',
            'disetujui_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<OpnameItem, $this>
     */
    public function item(): HasMany
    {
        return $this->hasMany(OpnameItem::class, 'id_opname', 'id_opname');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pembuat(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna', 'id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function penyetuju(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_penyetuju', 'id');
    }

    /**
     * Apakah seluruh item sudah diisi hasil hitung fisik.
     */
    public function semuaSudahDihitung(): bool
    {
        return $this->item()->whereNull('kuantitas_fisik')->doesntExist();
    }

    /**
     * Ringkasan selisih dokumen opname.
     *
     * @return array{item:int, selisih_plus:int, selisih_minus:int, produk_selisih:int}
     */
    public function ringkasanSelisih(): array
    {
        $item = $this->item()->get();

        return [
            'item' => $item->count(),
            'selisih_plus' => (int) $item->filter(fn (OpnameItem $b): bool => $b->selisih > 0)->sum('selisih'),
            'selisih_minus' => (int) $item->filter(fn (OpnameItem $b): bool => $b->selisih < 0)->sum('selisih'),
            'produk_selisih' => $item->filter(fn (OpnameItem $b): bool => $b->selisih !== 0)->count(),
        ];
    }

    /**
     * @param  Builder<Opname>  $query
     */
    public function scopeBetweenDates(Builder $query, ?string $dari, ?string $sampai): void
    {
        if ($dari !== null && $dari !== '') {
            $query->where('tanggal_opname', '>=', $dari);
        }

        if ($sampai !== null && $sampai !== '') {
            $query->where('tanggal_opname', '<=', $sampai);
        }
    }
}
