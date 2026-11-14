<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Baris produk pada sesi opname.
 *
 * `kuantitas_sistem` adalah snapshot saat sesi dibuat; `selisih` dihitung
 * `OpnameService` saat hasil hitung fisik diisi.
 */
#[Fillable(['id_opname', 'id_produk', 'kuantitas_sistem', 'kuantitas_fisik', 'selisih'])]
class OpnameItem extends Model
{
    protected $table = 'opname_item';

    protected $primaryKey = 'id_item';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kuantitas_sistem' => 'integer',
            'kuantitas_fisik' => 'integer',
            'selisih' => 'integer',
        ];
    }

    public function sudahDihitung(): bool
    {
        return $this->kuantitas_fisik !== null;
    }

    /**
     * @return BelongsTo<Opname, $this>
     */
    public function opname(): BelongsTo
    {
        return $this->belongsTo(Opname::class, 'id_opname', 'id_opname');
    }

    /**
     * @return BelongsTo<Stok, $this>
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Stok::class, 'id_produk', 'id_produk');
    }
}
