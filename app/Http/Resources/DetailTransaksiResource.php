<?php

namespace App\Http\Resources;

use App\Enums\JenisPembayaran;
use App\Models\DetailTransaksi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin DetailTransaksi
 */
class DetailTransaksiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $status = $this->statusBarang();

        return [
            'id_detail' => $this->id_detail,
            'id_transaksi' => $this->id_transaksi,
            'id_produk' => $this->id_produk,
            'produk' => $this->whenLoaded('produk', fn () => [
                'kode_sku' => $this->produk->kode_sku,
                'nama_produk' => $this->produk->nama_produk,
                'satuan' => $this->produk->satuan,
                'harga' => (int) $this->produk->harga,
            ]),
            'nama_produk' => $this->nama_produk_snapshot,
            'jumlah' => (int) $this->jumlah,
            // Status per BARANG (D-20a).
            'status_barang' => $status->value,
            'status_barang_label' => $status->label(),
            'harga_satuan' => $this->hargaSatuanEfektif(),
            'punya_modal' => $this->punyaModal(),
            'sub_total' => (float) $this->sub_total,
            'jenis_pembayaran' => $this->when(
                $this->transaksi !== null,
                fn () => $this->transaksi->jenis_pembayaran instanceof JenisPembayaran
                    ? $this->transaksi->jenis_pembayaran->value
                    : null,
            ),
        ];
    }
}
