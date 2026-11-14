<?php

namespace App\Models;

use App\Enums\JenisMutasiStok;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ledger pergerakan stok — append-only (tanpa `updated_at`).
 *
 * Invarian: Σ `jumlah_perubahan` per produk = `stok.kuantitas`.
 */
#[Fillable([
    'id_produk',
    'tanggal_mutasi',
    'jenis_mutasi',
    'jumlah_perubahan',
    'kuantitas_sebelum',
    'kuantitas_sesudah',
    'referensi_tipe',
    'referensi_id',
    'id_pengguna',
    'catatan',
])]
class MutasiStok extends Model
{
    protected $table = 'mutasi_stok';

    protected $primaryKey = 'id_mutasi';

    /**
     * Ledger bersifat immutable: hanya `created_at`.
     */
    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_mutasi' => 'datetime',
            'jenis_mutasi' => JenisMutasiStok::class,
            'jumlah_perubahan' => 'integer',
            'kuantitas_sebelum' => 'integer',
            'kuantitas_sesudah' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Stok, $this>
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Stok::class, 'id_produk', 'id_produk');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna', 'id');
    }

    /**
     * Rentang `tanggal_mutasi` memakai `>=` / `<=` agar index terpakai.
     *
     * @param  Builder<MutasiStok>  $query
     */
    public function scopeBetweenDates(Builder $query, ?string $dari, ?string $sampai): void
    {
        if ($dari !== null && $dari !== '') {
            $query->where('tanggal_mutasi', '>=', $dari.' 00:00:00');
        }

        if ($sampai !== null && $sampai !== '') {
            $query->where('tanggal_mutasi', '<=', $sampai.' 23:59:59');
        }
    }
}
