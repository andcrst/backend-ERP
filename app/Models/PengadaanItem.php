<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris produk yang diajukan dalam pengadaan.
 */
#[Fillable(['id_pengadaan', 'id_produk', 'jumlah_diminta', 'perkiraan_harga', 'jumlah_diterima', 'catatan'])]
class PengadaanItem extends Model
{
    protected $table = 'pengadaan_item';

    protected $primaryKey = 'id_item';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah_diminta' => 'integer',
            'perkiraan_harga' => 'integer',
            'jumlah_diterima' => 'integer',
        ];
    }

    /**
     * Sisa yang belum datang.
     */
    public function sisa(): int
    {
        return max(0, (int) $this->jumlah_diminta - (int) $this->jumlah_diterima);
    }

    /**
     * @return BelongsTo<Pengadaan, $this>
     */
    public function pengadaan(): BelongsTo
    {
        return $this->belongsTo(Pengadaan::class, 'id_pengadaan', 'id_pengadaan');
    }

    /**
     * @return BelongsTo<Stok, $this>
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Stok::class, 'id_produk', 'id_produk');
    }
}
