<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

/**
 * Pengaturan toko (key-value, CP-5).
 */
#[Fillable(['kunci', 'nilai', 'grup', 'keterangan'])]
class Pengaturan extends Model
{
    protected $table = 'pengaturan';

    protected $primaryKey = 'id_pengaturan';

    /**
     * Kunci yang dikenali sistem beserta nilai default daftar aplikasinya.
     *
     * @return array<string, array{default:string|null, grup:string, keterangan:string}>
     */
    public static function bawaan(): array
    {
        return [
            'nama_toko' => [
                'default' => config('app.name'),
                'grup' => 'identitas',
                'keterangan' => 'Nama toko yang tampil di aplikasi & struk',
            ],
            'alamat_toko' => [
                'default' => null,
                'grup' => 'identitas',
                'keterangan' => 'Alamat toko',
            ],
            'telepon_toko' => [
                'default' => null,
                'grup' => 'identitas',
                'keterangan' => 'Telepon toko',
            ],
            'prefix_struk' => [
                'default' => (string) config('erp.dokumen.struk', 'INV'),
                'grup' => 'dokumen',
                'keterangan' => 'Prefix nomor struk',
            ],
            'stok_minimum_default' => [
                'default' => (string) config('erp.stok.minimum_default', 5),
                'grup' => 'stok',
                'keterangan' => 'Stok minimum default produk baru',
            ],
            'biaya_angkut_default' => [
                'default' => (string) config('erp.biaya_angkut.default', 0),
                'grup' => 'pengiriman',
                'keterangan' => 'Biaya angkut default (ditanggung toko)',
            ],
        ];
    }
}
