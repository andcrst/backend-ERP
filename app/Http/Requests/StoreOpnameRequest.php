<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Buat sesi opname (G-06) — cakupan seluruh produk, satu kategori, atau produk tertentu.
 */
class StoreOpnameRequest extends FormRequest
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
            'tanggal_opname' => ['nullable', 'date'],
            'id_kategori' => ['nullable', 'integer', Rule::exists('kategori', 'id_kategori')],
            'id_produk' => ['nullable', 'array', 'min:1'],
            'id_produk.*' => ['integer', Rule::exists('stok', 'id_produk')],
            'catatan' => ['nullable', 'string', 'max:1000'],

            // Snapshot kuantitas sistem diambil server, bukan dari payload.
            'kuantitas_sistem' => ['prohibited'],
            'kuantitas_fisik' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal_opname.date' => 'Tanggal opname tidak valid.',
            'id_kategori.integer' => 'Kategori tidak valid.',
            'id_kategori.exists' => 'Kategori tidak ditemukan.',
            'id_produk.array' => 'Daftar produk tidak valid.',
            'id_produk.*.exists' => 'Produk tidak ditemukan.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
            'kuantitas_sistem.prohibited' => 'Kuantitas sistem diambil otomatis oleh sistem.',
            'kuantitas_fisik.prohibited' => 'Hasil hitung fisik diisi lewat endpoint hitung.',
        ];
    }
}
