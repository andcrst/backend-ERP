<?php

namespace App\Http\Resources;

use App\Models\Penerimaan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Penerimaan
 */
class PenerimaanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_penerimaan' => $this->id_penerimaan,
            'nomor_penerimaan' => $this->nomor_penerimaan,
            'tanggal_penerimaan' => $this->tanggal_penerimaan?->toDateString(),
            'id_pengadaan' => $this->id_pengadaan,
            'pengadaan' => $this->whenLoaded('pengadaan', fn () => $this->pengadaan === null
                ? null
                : [
                    'id_pengadaan' => $this->pengadaan->id_pengadaan,
                    'nomor_pengadaan' => $this->pengadaan->nomor_pengadaan,
                    'status' => $this->pengadaan->status->value,
                ]),
            'id_supplier' => $this->id_supplier,
            'supplier' => $this->whenLoaded('supplier', fn () => $this->supplier === null
                ? null
                : [
                    'id_supplier' => $this->supplier->id_supplier,
                    'nama_supplier' => $this->supplier->nama_supplier,
                ]),
            'nomor_surat_jalan' => $this->nomor_surat_jalan,
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'sudah_diposting' => $this->sudahDiposting(),
            'total_nilai' => (float) $this->total_nilai,
            'catatan' => $this->catatan,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'id_pengguna' => $this->id_pengguna,
            'pengguna' => $this->whenLoaded('pengguna', fn () => $this->pengguna === null
                ? null
                : ['id' => $this->pengguna->id, 'name' => $this->pengguna->name]),
            'item' => PenerimaanItemResource::collection($this->whenLoaded('item')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
