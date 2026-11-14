<?php

namespace App\Http\Resources;

use App\Models\OpnameItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin OpnameItem
 */
class OpnameItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_item' => $this->id_item,
            'id_produk' => $this->id_produk,
            'produk' => $this->whenLoaded('produk', fn () => $this->produk === null
                ? null
                : [
                    'kode_sku' => $this->produk->kode_sku,
                    'nama_produk' => $this->produk->nama_produk,
                    'satuan' => $this->produk->satuan,
                ]),
            'kuantitas_sistem' => (int) $this->kuantitas_sistem,
            'kuantitas_fisik' => $this->kuantitas_fisik === null ? null : (int) $this->kuantitas_fisik,
            'selisih' => (int) $this->selisih,
            'sudah_dihitung' => $this->sudahDihitung(),
        ];
    }
}
