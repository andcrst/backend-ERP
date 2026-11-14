<?php

namespace App\Http\Requests;

use App\Enums\JenisPembayaran;
use App\Enums\StatusBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validasi pembuatan pesanan kasir (CP-1).
 *
 * Status per BARANG (D-20a): hanya DIAMBIL / MENUNGGU_DIANTAR yang boleh
 * dikirim saat pembuatan. Nilai lama (DIAMBIL_NANTI, DISIAPKAN) ditolak (B11).
 */
class StorePesananRequest extends FormRequest
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
            'detail' => ['required', 'array', 'min:1'],
            'detail.*.id_produk' => ['required', 'integer', 'exists:stok,id_produk', 'distinct'],
            'detail.*.jumlah' => ['required', 'integer', 'min:1'],
            'detail.*.status_barang' => [
                'nullable',
                'string',
                Rule::in([StatusBarang::Diambil->value, StatusBarang::MenungguDiantar->value]),
            ],

            'jenis_pembayaran' => ['required', 'string', Rule::in(JenisPembayaran::values())],
            'jumlah_bayar' => ['nullable', 'numeric', 'min:0'],
            'referensi_pembayaran' => ['nullable', 'string', 'max:100'],

            'id_pelanggan' => ['nullable', 'integer', 'exists:pelanggan,id_pelanggan'],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'detail.required' => 'Transaksi harus memiliki minimal satu barang.',
            'detail.min' => 'Transaksi harus memiliki minimal satu barang.',
            'detail.*.id_produk.required' => 'Setiap baris harus menyertakan produk.',
            'detail.*.id_produk.exists' => 'Produk tidak ditemukan pada tabel stok.',
            'detail.*.id_produk.distinct' => 'Produk tidak boleh muncul lebih dari sekali.',
            'detail.*.jumlah.required' => 'Jumlah barang wajib diisi.',
            'detail.*.jumlah.integer' => 'Jumlah barang harus berupa angka bulat.',
            'detail.*.jumlah.min' => 'Jumlah barang minimal 1.',
            'detail.*.status_barang.in' => 'Status barang hanya boleh DIAMBIL atau MENUNGGU_DIANTAR.',
            'jenis_pembayaran.required' => 'Jenis pembayaran wajib diisi.',
            'jenis_pembayaran.in' => 'Jenis pembayaran harus TUNAI, QRIS, atau TRANSFER.',
            'jumlah_bayar.numeric' => 'Jumlah bayar harus berupa angka.',
            'jumlah_bayar.min' => 'Jumlah bayar tidak boleh negatif.',
            'referensi_pembayaran.max' => 'Referensi pembayaran maksimal 100 karakter.',
            'id_pelanggan.exists' => 'Pelanggan tidak ditemukan.',
        ];
    }
}
