<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Jejak audit aksi sensitif (AR9). Append-only — tanpa `updated_at`.
 */
#[Fillable([
    'id_pengguna',
    'aksi',
    'entitas_tipe',
    'entitas_id',
    'data_sebelum',
    'data_sesudah',
    'ip_address',
    'user_agent',
])]
class AuditLog extends Model
{
    protected $table = 'audit_log';

    protected $primaryKey = 'id_audit';

    public const UPDATED_AT = null;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'data_sebelum' => 'array',
            'data_sesudah' => 'array',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function pengguna(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_pengguna', 'id');
    }
}
