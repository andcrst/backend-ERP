<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Hitung fisik satu baris opname (GUDANG).
 */
class UpdateOpnameItemRequest extends FormRequest
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
            'kuantitas_fisik' => ['required', 'integer', 'min:0'],

            // Selisih & kuantitas sistem dihitung server.
            'selisih' => ['prohibited'],
            'kuantitas_sistem' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kuantitas_fisik.required' => 'Hasil hitung fisik wajib diisi.',
            'kuantitas_fisik.integer' => 'Hasil hitung fisik harus berupa angka bulat.',
            'kuantitas_fisik.min' => 'Hasil hitung fisik tidak boleh negatif.',
            'selisih.prohibited' => 'Selisih dihitung otomatis oleh sistem.',
            'kuantitas_sistem.prohibited' => 'Kuantitas sistem tidak dapat diubah.',
        ];
    }
}
