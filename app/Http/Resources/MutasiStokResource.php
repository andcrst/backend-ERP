<?php

namespace App\Http\Resources;

use App\Models\MutasiStok;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Satu baris kartu stok (ledger `mutasi_stok`).
 *
 * @mixin MutasiStok
 */
class MutasiStokResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $jumlah = (int) $this->jumlah_perubahan;

        return [
            'id_mutasi' => $this->id_mutasi,
            'id_produk' => $this->id_produk,
            'produk' => $this->whenLoaded('produk', fn () => $this->produk === null
                ? null
                : [
                    'kode_sku' => $this->produk->kode_sku,
                    'nama_produk' => $this->produk->nama_produk,
                    'satuan' => $this->produk->satuan,
                ]),
            'tanggal_mutasi' => $this->tanggal_mutasi?->toIso8601String(),
            'jenis_mutasi' => $this->jenis_mutasi->value,
            'jenis_mutasi_label' => $this->jenis_mutasi->label(),
            'jumlah_perubahan' => $jumlah,
            'arah' => $jumlah >= 0 ? 'MASUK' : 'KELUAR',
            'kuantitas_sebelum' => (int) $this->kuantitas_sebelum,
            'kuantitas_sesudah' => (int) $this->kuantitas_sesudah,
            'referensi_tipe' => $this->referensi_tipe,
            'referensi_id' => $this->referensi_id === null ? null : (int) $this->referensi_id,
            'id_pengguna' => $this->id_pengguna,
            'pengguna' => $this->whenLoaded('pengguna', fn () => $this->pengguna === null
                ? null
                : ['id' => $this->pengguna->id, 'name' => $this->pengguna->name]),
            'catatan' => $this->catatan,
        ];
    }
}
