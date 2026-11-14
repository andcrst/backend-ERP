<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStokRequest extends FormRequest
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
        $stok = $this->route('stok');
        $isUpdate = $stok !== null;

        return [
            'kode_sku' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('stok', 'kode_sku')->ignore($stok?->id_produk, 'id_produk'),
            ],
            'nama_produk' => [Rule::requiredIf(! $isUpdate), 'string', 'max:150'],
            'id_kategori' => [
                'nullable',
                'integer',
                Rule::exists('kategori', 'id_kategori'),
            ],
            'satuan' => ['sometimes', 'string', 'max:20'],
            'harga' => [Rule::requiredIf(! $isUpdate), 'integer', 'min:0'],
            'harga_modal' => ['nullable', 'integer', 'min:0'],
            'stok_minimum' => ['sometimes', 'integer', 'min:0'],

            // D-09: kuantitas TIDAK boleh diubah lewat CRUD stok.
            'kuantitas' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kode_sku.unique' => 'Kode SKU sudah digunakan.',
            'nama_produk.required' => 'Nama produk wajib diisi.',
            'nama_produk.max' => 'Nama produk maksimal 150 karakter.',
            'harga.required' => 'Harga wajib diisi.',
            'harga.integer' => 'Harga harus berupa angka bulat (rupiah).',
            'harga.min' => 'Harga tidak boleh negatif.',
            'harga_modal.integer' => 'Harga modal harus berupa angka bulat (rupiah).',
            'harga_modal.min' => 'Harga modal tidak boleh negatif.',
            'stok_minimum.integer' => 'Stok minimum harus berupa angka bulat.',
            'stok_minimum.min' => 'Stok minimum tidak boleh negatif.',
            'satuan.max' => 'Satuan maksimal 20 karakter.',
            'kode_sku.max' => 'Kode SKU maksimal 50 karakter.',
            'id_kategori.integer' => 'Kategori tidak valid.',
            'id_kategori.exists' => 'Kategori tidak ditemukan.',
            'kuantitas.prohibited' => 'Jumlah stok tidak dapat diubah dari master produk. Gunakan penerimaan barang, penyesuaian, atau opname.',
        ];
    }
}
