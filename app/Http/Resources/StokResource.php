<?php

namespace App\Http\Resources;

use App\Models\Stok;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Stok
 */
class StokResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_produk' => $this->id_produk,
            'kode_sku' => $this->kode_sku,
            'nama_produk' => $this->nama_produk,
            'satuan' => $this->satuan,
            'id_kategori' => $this->id_kategori,
            'kategori' => $this->whenLoaded('kategori', fn () => $this->kategori === null
                ? null
                : [
                    'id_kategori' => $this->kategori->id_kategori,
                    'nama_kategori' => $this->kategori->nama_kategori,
                ]),
            'harga' => (int) $this->harga,
            // D-05=A: modal menjadi dasar HPP. null = belum diisi.
            'harga_modal' => $this->harga_modal === null ? null : (int) $this->harga_modal,
            'punya_modal' => $this->punyaModal(),
            'kuantitas' => (int) $this->kuantitas,
            'stok_minimum' => (int) $this->stok_minimum,
            'stok_menipis' => $this->menipis(),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
