<?php

namespace App\Http\Resources;

use App\Models\PengadaanItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PengadaanItem
 */
class PengadaanItemResource extends JsonResource
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
                    'harga_modal' => $this->produk->harga_modal === null
                        ? null
                        : (int) $this->produk->harga_modal,
                ]),
            'jumlah_diminta' => (int) $this->jumlah_diminta,
            'jumlah_diterima' => (int) $this->jumlah_diterima,
            'sisa' => $this->sisa(),
            'perkiraan_harga' => $this->perkiraan_harga === null ? null : (int) $this->perkiraan_harga,
            'catatan' => $this->catatan,
        ];
    }
}
