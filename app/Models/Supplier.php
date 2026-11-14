<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Master supplier (D-03=A) — dipakai pengajuan pengadaan & penerimaan.
 */
#[Fillable(['nama_supplier', 'telepon', 'alamat', 'catatan', 'is_active'])]
class Supplier extends Model
{
    use SoftDeletes;

    protected $table = 'supplier';

    protected $primaryKey = 'id_supplier';

    /**
     * Route model binding memakai PK kustom (bukan `id`).
     */
    public function getRouteKeyName(): string
    {
        return 'id_supplier';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Pencarian nama / telepon supplier.
     *
     * @param  Builder<Supplier>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || trim($term) === '') {
            return;
        }

        $like = '%'.trim($term).'%';

        $query->where(function (Builder $builder) use ($like): void {
            $builder->where('nama_supplier', 'like', $like)
                ->orWhere('telepon', 'like', $like);
        });
    }

    /**
     * @param  Builder<Supplier>  $query
     */
    public function scopeAktifSaja(Builder $query, bool $aktifSaja): void
    {
        if (! $aktifSaja) {
            return;
        }

        $query->where('is_active', true);
    }
}
