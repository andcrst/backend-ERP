<?php

namespace App\Http\Resources;

use App\Models\Pengeluaran;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Pengeluaran
 */
class PengeluaranResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id_pengeluaran' => $this->id_pengeluaran,
            'nomor_pengeluaran' => $this->nomor_pengeluaran,
            'tanggal_pengeluaran' => $this->tanggal_pengeluaran?->toDateString(),
            'alasan' => $this->alasan->value,
            'alasan_label' => $this->alasan->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'sudah_diposting' => $this->sudahDiposting(),
            'id_pengguna' => $this->id_pengguna,
            'pengguna' => $this->whenLoaded('pengguna', fn () => $this->pengguna === null
                ? null
                : ['id' => $this->pengguna->id, 'name' => $this->pengguna->name]),
            'catatan' => $this->catatan,
            'posted_at' => $this->posted_at?->toIso8601String(),
            'item' => PengeluaranItemResource::collection($this->whenLoaded('item')),
            'total_unit' => $this->whenLoaded('item', fn () => (int) $this->item->sum('jumlah')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
