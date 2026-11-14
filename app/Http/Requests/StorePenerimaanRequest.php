<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Penerimaan barang (G-02) — harga beli wajib, barang harus dari pengajuan.
 */
class StorePenerimaanRequest extends FormRequest
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
            'tanggal_penerimaan' => ['nullable', 'date'],
            'nomor_surat_jalan' => ['nullable', 'string', 'max:60'],
            'catatan' => ['nullable', 'string', 'max:1000'],

            'item' => ['required', 'array', 'min:1'],
            'item.*.id_produk' => [
                'required',
                'integer',
                Rule::exists('stok', 'id_produk'),
                'distinct',
            ],
            'item.*.jumlah' => ['required', 'integer', 'min:1'],
            'item.*.harga_modal' => ['required', 'integer', 'min:0'],
            'item.*.catatan' => ['nullable', 'string', 'max:255'],

            // Dihitung/diisi server.
            'total_nilai' => ['prohibited'],
            'status' => ['prohibited'],
            'nomor_penerimaan' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal_penerimaan.date' => 'Tanggal penerimaan tidak valid.',
            'nomor_surat_jalan.max' => 'Nomor surat jalan maksimal 60 karakter.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
            'item.required' => 'Minimal satu barang wajib diisi.',
            'item.min' => 'Minimal satu barang wajib diisi.',
            'item.*.id_produk.required' => 'Produk wajib dipilih.',
            'item.*.id_produk.exists' => 'Produk tidak ditemukan.',
            'item.*.id_produk.distinct' => 'Produk tidak boleh dipilih dua kali.',
            'item.*.jumlah.required' => 'Jumlah diterima wajib diisi.',
            'item.*.jumlah.min' => 'Jumlah diterima minimal 1.',
            'item.*.harga_modal.required' => 'Harga beli (modal) wajib diisi.',
            'item.*.harga_modal.min' => 'Harga beli tidak boleh negatif.',
            'item.*.catatan.max' => 'Catatan barang maksimal 255 karakter.',
            'total_nilai.prohibited' => 'Total nilai dihitung otomatis oleh sistem.',
            'status.prohibited' => 'Status ditentukan oleh sistem.',
            'nomor_penerimaan.prohibited' => 'Nomor penerimaan dibuat otomatis.',
        ];
    }
}
