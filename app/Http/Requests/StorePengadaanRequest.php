<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Pengajuan pengadaan (G-01) & pengajuan ulang setelah revisi/ditolak.
 */
class StorePengadaanRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tanggal_pengadaan' => ['nullable', 'date'],
            'tanggal_kebutuhan' => ['nullable', 'date'],
            'id_supplier' => ['nullable', 'integer', Rule::exists('supplier', 'id_supplier')],
            'alasan' => ['nullable', 'string', 'max:1000'],

            'item' => ['required', 'array', 'min:1'],
            'item.*.id_produk' => [
                'required',
                'integer',
                Rule::exists('stok', 'id_produk'),
                'distinct',
            ],
            'item.*.jumlah_diminta' => ['required', 'integer', 'min:1'],
            'item.*.perkiraan_harga' => ['nullable', 'integer', 'min:0'],
            'item.*.catatan' => ['nullable', 'string', 'max:255'],

            // Status & nomor ditentukan server.
            'status' => ['prohibited'],
            'nomor_pengadaan' => ['prohibited'],
            'jumlah_diterima' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal_pengadaan.date' => 'Tanggal pengadaan tidak valid.',
            'tanggal_kebutuhan.date' => 'Tanggal kebutuhan tidak valid.',
            'id_supplier.exists' => 'Supplier tidak ditemukan.',
            'alasan.max' => 'Alasan maksimal 1000 karakter.',
            'item.required' => 'Minimal satu barang wajib diisi.',
            'item.min' => 'Minimal satu barang wajib diisi.',
            'item.*.id_produk.required' => 'Produk wajib dipilih.',
            'item.*.id_produk.exists' => 'Produk tidak ditemukan.',
            'item.*.id_produk.distinct' => 'Produk tidak boleh dipilih dua kali.',
            'item.*.jumlah_diminta.required' => 'Jumlah diminta wajib diisi.',
            'item.*.jumlah_diminta.min' => 'Jumlah diminta minimal 1.',
            'item.*.perkiraan_harga.min' => 'Perkiraan harga tidak boleh negatif.',
            'item.*.catatan.max' => 'Catatan barang maksimal 255 karakter.',
            'status.prohibited' => 'Status ditentukan oleh sistem.',
            'nomor_pengadaan.prohibited' => 'Nomor pengadaan dibuat otomatis.',
            'jumlah_diterima.prohibited' => 'Jumlah diterima diisi lewat penerimaan barang.',
        ];
    }
}
