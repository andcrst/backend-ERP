<?php

namespace App\Http\Resources;

use App\Models\PengeluaranItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PengeluaranItem
 */
class PengeluaranItemResource extends JsonResource
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
                    'kuantitas' => (int) $this->produk->kuantitas,
                ]),
            'jumlah' => (int) $this->jumlah,
            'catatan' => $this->catatan,
        ];
    }
}
