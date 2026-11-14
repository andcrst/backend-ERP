<?php

namespace App\Models;

use App\Enums\StatusBarang;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'id_transaksi',
    'id_produk',
    'jumlah',
    'status_barang',
    'harga_satuan',
    'nama_produk_snapshot',
    'harga_modal_snapshot',
    'sub_total',
])]
class DetailTransaksi extends Model
{
    use HasFactory;

    protected $table = 'detail_transaksi';

    protected $primaryKey = 'id_detail';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'jumlah' => 'integer',
            'harga_satuan' => 'integer',
            'harga_modal_snapshot' => 'integer',
            'sub_total' => 'decimal:2',
        ];
    }

    /**
     * Status barang sebagai enum.
     */
    public function statusBarang(): StatusBarang
    {
        $nilai = $this->status_barang;

        return $nilai instanceof StatusBarang ? $nilai : StatusBarang::from((string) $nilai);
    }

    /**
     * Apakah harga modal diketahui saat transaksi (dasar HPP).
     */
    public function punyaModal(): bool
    {
        return $this->harga_modal_snapshot !== null;
    }

    /**
     * Harga satuan efektif: snapshot, atau fallback ke harga produk (data lama).
     */
    public function hargaSatuanEfektif(): int
    {
        if ($this->harga_satuan !== null) {
            return (int) $this->harga_satuan;
        }

        return (int) ($this->produk?->harga ?? 0);
    }

    /**
     * @return BelongsTo<Transaksi, $this>
     */
    public function transaksi(): BelongsTo
    {
        return $this->belongsTo(Transaksi::class, 'id_transaksi', 'id_transaksi');
    }

    /**
     * @return BelongsTo<Stok, $this>
     */
    public function produk(): BelongsTo
    {
        return $this->belongsTo(Stok::class, 'id_produk', 'id_produk');
    }
}
