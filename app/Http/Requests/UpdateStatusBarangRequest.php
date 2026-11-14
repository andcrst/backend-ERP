<?php

namespace App\Http\Requests;

use App\Enums\StatusBarang;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Ubah status satu barang (oleh Petugas Gudang).
 */
class UpdateStatusBarangRequest extends FormRequest
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
            'status_barang' => [
                'required',
                'string',
                Rule::in([StatusBarang::SudahDiantar->value, StatusBarang::GagalDiantar->value]),
            ],
            'catatan' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status_barang.required' => 'Status barang wajib diisi.',
            'status_barang.in' => 'Status barang hanya boleh SUDAH_DIANTAR atau GAGAL_DIANTAR.',
            'catatan.max' => 'Catatan maksimal 255 karakter.',
        ];
    }
}
