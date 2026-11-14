<?php

namespace App\Http\Resources;

use App\Enums\JenisPembayaran;
use App\Models\Transaksi;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaksi
 */
class TransaksiResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $jenis = $this->jenis_pembayaran;

        return [
            'id_transaksi' => $this->id_transaksi,
            'nomor_struk' => $this->nomor_struk,
            'id_pengguna' => $this->id_pengguna,
            'pengguna' => $this->whenLoaded('pengguna', fn () => [
                'nama' => $this->pengguna->name,
                'email' => $this->pengguna->email,
            ]),

            'id_pelanggan' => $this->id_pelanggan,
            'pelanggan' => $this->whenLoaded('pelanggan', fn () => $this->pelanggan === null ? null : [
                'id_pelanggan' => $this->pelanggan->id_pelanggan,
                'nama_pelanggan' => $this->pelanggan->nama_pelanggan,
                'telepon' => $this->pelanggan->telepon,
                'alamat' => $this->pelanggan->alamat,
            ]),

            'tanggal_transaksi' => $this->tanggal_transaksi?->toIso8601String(),
            'total_pembayaran' => (float) $this->total_pembayaran,
            'jenis_pembayaran' => $jenis instanceof JenisPembayaran ? $jenis->value : $jenis,
            'jenis_pembayaran_label' => $jenis instanceof JenisPembayaran ? $jenis->label() : null,

            'status_pembayaran' => $this->status_pembayaran,
            'jumlah_bayar' => $this->jumlah_bayar === null ? null : (float) $this->jumlah_bayar,
            'referensi_pembayaran' => $this->referensi_pembayaran,
            'kembalian' => (float) $this->kembalian,

            // Biaya angkut DITANGGUNG TOKO (D-32=B): bukan bagian total tagihan.
            'biaya_angkut' => (int) $this->biaya_angkut,
            'biaya_angkut_ditanggung_toko' => true,

            // Status transaksi dihitung dari status barangnya (D-20a).
            'status_pesanan' => $this->statusPesanan(),
            'perlu_diantar' => $this->perluDiantar(),

            'detail' => DetailTransaksiResource::collection($this->whenLoaded('detail')),
            'detail_count' => $this->whenCounted('detail'),
            'catatan' => $this->catatan,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
