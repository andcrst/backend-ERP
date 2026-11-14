<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris barang yang benar-benar diterima.
 *
 * `harga_modal` wajib diisi saat posting — menjadi dasar HPP (D-05=A).
 */
#[Fillable(['id_penerimaan', 'id_produk', 'jumlah', 'harga_modal', 'sub_total', 'catatan'])]
class PenerimaanItem extends Model
{
    protected $table = 'penerimaan_item';

    protected $primaryKey = 'id_item';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'harga_modal' => 'integer',
            'sub_total' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Penerimaan, $this>
     */
    public function penerimaan(): BelongsTo
    {
        return $this->belongsTo(Penerimaan::class, 'id_penerimaan', 'id_penerimaan');
    }

    /**
     * @return BelongsTo<Stok, $this>
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Stok::class, 'id_produk', 'id_produk');
    }
}
