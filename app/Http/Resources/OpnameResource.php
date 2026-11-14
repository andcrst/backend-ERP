<?php

namespace App\Http\Resources;

use App\Models\Opname;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Opname
 */
class OpnameResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_opname' => $this->id_opname,
            'nomor_opname' => $this->nomor_opname,
            'tanggal_opname' => $this->tanggal_opname?->toDateString(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'id_pengguna' => $this->id_pengguna,
            'pembuat' => $this->whenLoaded('pembuat', fn () => $this->pembuat === null
                ? null
                : ['id' => $this->pembuat->id, 'name' => $this->pembuat->name]),
            'id_penyetuju' => $this->id_penyetuju,
            'penyetuju' => $this->whenLoaded('penyetuju', fn () => $this->penyetuju === null
                ? null
                : ['id' => $this->penyetuju->id, 'name' => $this->penyetuju->name]),
            'dihitung_at' => $this->dihitung_at?->toIso8601String(),
            'disetujui_at' => $this->disetujui_at?->toIso8601String(),
            'catatan' => $this->catatan,
            'ringkasan' => $this->when(
                $this->relationLoaded('item'),
                fn () => $this->ringkasanSelisih(),
            ),
            'item' => OpnameItemResource::collection($this->whenLoaded('item')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
