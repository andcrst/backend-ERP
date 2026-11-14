<?php

namespace App\Http\Resources;

use App\Models\PenerimaanItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PenerimaanItem
 */
class PenerimaanItemResource extends JsonResource
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
            'harga_modal' => $this->harga_modal === null ? null : (int) $this->harga_modal,
            'sub_total' => (float) $this->sub_total,
            'catatan' => $this->catatan,
        ];
    }
}
