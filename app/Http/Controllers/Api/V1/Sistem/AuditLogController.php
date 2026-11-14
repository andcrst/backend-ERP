<?php

namespace App\Http\Controllers\Api\V1\Sistem;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Jejak audit (CP-5) — baca-saja.
 */
class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min((int) $request->query('per_page', 20), 100);

        $data = AuditLog::query()
            ->with('pengguna:id,name,email')
            ->when($request->query('aksi'), fn ($q, $aksi) => $q->where('aksi', $aksi))
            ->when($request->query('entitas_tipe'), fn ($q, $tipe) => $q->where('entitas_tipe', $tipe))
            ->when($request->query('id_pengguna'), fn ($q, $id) => $q->where('id_pengguna', $id))
            ->when($request->query('dari'), fn ($q, $dari) => $q->where('created_at', '>=', $dari.' 00:00:00'))
            ->when($request->query('sampai'), fn ($q, $sampai) => $q->where('created_at', '<=', $sampai.' 23:59:59'))
            ->orderByDesc('id_audit')
            ->paginate($perPage)
            ->through(fn (AuditLog $item): array => [
                'id_audit' => $item->id_audit,
                'aksi' => $item->aksi,
                'entitas_tipe' => $item->entitas_tipe,
                'entitas_id' => $item->entitas_id,
                'id_pengguna' => $item->id_pengguna,
                'pengguna' => $item->pengguna === null ? null : [
                    'id' => $item->pengguna->id,
                    'name' => $item->pengguna->name,
                    'email' => $item->pengguna->email,
                ],
                'data_sebelum' => $item->data_sebelum,
                'data_sesudah' => $item->data_sesudah,
                'ip_address' => $item->ip_address,
                'created_at' => $item->created_at?->toIso8601String(),
            ]);

        return ApiResponse::paginated($data, 'Jejak audit.');
    }
}
