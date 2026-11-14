<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Isi biaya angkut (oleh Petugas Gudang). D-31 + D-32=B.
 */
class UpdateBiayaAngkutRequest extends FormRequest
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
            'biaya_angkut' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'biaya_angkut.required' => 'Biaya angkut wajib diisi (boleh 0).',
            'biaya_angkut.integer' => 'Biaya angkut harus berupa angka bulat (rupiah).',
            'biaya_angkut.min' => 'Biaya angkut tidak boleh negatif.',
        ];
    }
}
