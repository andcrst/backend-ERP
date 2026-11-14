<?php

namespace App\Http\Requests;

use App\Enums\AlasanPengeluaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Dokumen barang keluar (G-05) — alasan WAJIB.
 *
 * `kuantitas` tidak boleh diisi; pengurangan stok hanya lewat posting.
 */
class StorePengeluaranRequest extends FormRequest
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
            'tanggal_pengeluaran' => ['nullable', 'date'],
            'alasan' => ['required', Rule::in(AlasanPengeluaran::values())],
            'catatan' => ['nullable', 'string', 'max:1000'],

            'item' => ['required', 'array', 'min:1'],
            'item.*.id_produk' => [
                'required',
                'integer',
                Rule::exists('stok', 'id_produk'),
                'distinct',
            ],
            'item.*.jumlah' => ['required', 'integer', 'min:1'],
            'item.*.catatan' => ['nullable', 'string', 'max:255'],

            // Stok hanya berubah lewat posting, bukan dari payload.
            'kuantitas' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan.required' => 'Alasan pengeluaran wajib dipilih.',
            'alasan.in' => 'Alasan pengeluaran tidak dikenal.',
            'tanggal_pengeluaran.date' => 'Tanggal pengeluaran tidak valid.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
            'item.required' => 'Minimal satu barang wajib diisi.',
            'item.min' => 'Minimal satu barang wajib diisi.',
            'item.*.id_produk.required' => 'Produk wajib dipilih.',
            'item.*.id_produk.exists' => 'Produk tidak ditemukan.',
            'item.*.id_produk.distinct' => 'Produk tidak boleh dipilih dua kali.',
            'item.*.jumlah.required' => 'Jumlah wajib diisi.',
            'item.*.jumlah.min' => 'Jumlah minimal 1.',
            'item.*.catatan.max' => 'Catatan barang maksimal 255 karakter.',
            'kuantitas.prohibited' => 'Jumlah stok tidak dapat diubah langsung. Gunakan pengeluaran, penyesuaian, atau opname.',
        ];
    }
}
