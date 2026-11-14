<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['nama_pelanggan', 'telepon', 'alamat', 'catatan', 'is_active'])]
class Pelanggan extends Model
{
    use SoftDeletes;

    protected $table = 'pelanggan';

    protected $primaryKey = 'id_pelanggan';

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
     * @return HasMany<Transaksi, $this>
     */
    public function transaksi(): HasMany
    {
        return $this->hasMany(Transaksi::class, 'id_pelanggan', 'id_pelanggan');
    }

    /**
     * Alamat wajib terisi untuk barang yang perlu diantar (K-02).
     */
    public function punyaAlamat(): bool
    {
        return is_string($this->alamat) && trim($this->alamat) !== '';
    }

    /**
     * @param  Builder<Pelanggan>  $query
     */
    public function scopeSearch(Builder $query, ?string $term): void
    {
        if ($term === null || trim($term) === '') {
            return;
        }

        $like = '%'.trim($term).'%';

        $query->where(function (Builder $q) use ($like): void {
            $q->where('nama_pelanggan', 'like', $like)
                ->orWhere('telepon', 'like', $like)
                ->orWhere('alamat', 'like', $like);
        });
    }
}
