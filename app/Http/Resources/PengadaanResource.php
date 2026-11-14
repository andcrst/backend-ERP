<?php

namespace App\Http\Resources;

use App\Models\Pengadaan;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pengadaan
 */
class PengadaanResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_pengadaan' => $this->id_pengadaan,
            'nomor_pengadaan' => $this->nomor_pengadaan,
            'tanggal_pengadaan' => $this->tanggal_pengadaan?->toDateString(),
            'tanggal_kebutuhan' => $this->tanggal_kebutuhan?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'id_supplier' => $this->id_supplier,
            'supplier' => $this->whenLoaded('supplier', fn () => $this->supplier === null
                ? null
                : [
                    'id_supplier' => $this->supplier->id_supplier,
                    'nama_supplier' => $this->supplier->nama_supplier,
                ]),
            'id_pengaju' => $this->id_pengaju,
            'pengaju' => $this->whenLoaded('pengaju', fn () => $this->pengaju === null
                ? null
                : ['id' => $this->pengaju->id, 'name' => $this->pengaju->name]),
            'id_penyetuju' => $this->id_penyetuju,
            'penyetuju' => $this->whenLoaded('penyetuju', fn () => $this->penyetuju === null
                ? null
                : ['id' => $this->penyetuju->id, 'name' => $this->penyetuju->name]),
            'alasan' => $this->alasan,
            'catatan_keputusan' => $this->catatan_keputusan,
            'diputuskan_at' => $this->diputuskan_at?->toIso8601String(),
            'diterima_at' => $this->diterima_at?->toIso8601String(),
            'ditutup_at' => $this->ditutup_at?->toIso8601String(),
            'dapat_diputuskan' => $this->status->dapatDiputuskan(),
            'dapat_diterima' => $this->status->dapatDiterima(),
            'dapat_ditutup' => $this->status->dapatDitutup(),
            'item' => PengadaanItemResource::collection($this->whenLoaded('item')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
