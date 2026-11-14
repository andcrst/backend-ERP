<?php

namespace App\Models;

use App\Enums\StatusDokumen;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Penerimaan barang dari supplier (G-02).
 *
 * Selalu terikat pengadaan yang DISETUJUI. Draf belum menyentuh stok;
 * posting menaikkan stok dan memperbarui `stok.harga_modal` (D-05=A).
 */
#[Fillable([
    'nomor_penerimaan',
    'tanggal_penerimaan',
    'id_pengadaan',
    'id_supplier',
    'nomor_surat_jalan',
    'id_pengguna',
    'status',
    'total_nilai',
    'catatan',
    'posted_at',
])]
class Penerimaan extends Model
{
    protected $table = 'penerimaan';

    protected $primaryKey = 'id_penerimaan';

    /**
     * Route model binding memakai PK kustom (bukan `id`).
     */
    public function getRouteKeyName(): string
    {
        return 'id_penerimaan';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_penerimaan' => 'date',
            'status' => StatusDokumen::class,
            'total_nilai' => 'decimal:2',
            'posted_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<PenerimaanItem, $this>
     */
    public function item(): HasMany
    {
        return $this->hasMany(PenerimaanItem::class, 'id_penerimaan', 'id_penerimaan');
    }

    /**
     * @return BelongsTo<Pengadaan, $this>
     */
    public function pengadaan(): BelongsTo
    {
        return $this->belongsTo(Pengadaan::class, 'id_pengadaan', 'id_pengadaan');
    }

    /**
     * @return BelongsTo<Supplier, $this>
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class, 'id_supplier', 'id_supplier');
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
     * @param  Builder<Penerimaan>  $query
     */
    public function scopeBetweenDates(Builder $query, ?string $dari, ?string $sampai): void
    {
        if ($dari !== null && $dari !== '') {
            $query->where('tanggal_penerimaan', '>=', $dari);
        }

        if ($sampai !== null && $sampai !== '') {
            $query->where('tanggal_penerimaan', '<=', $sampai);
        }
    }
}
