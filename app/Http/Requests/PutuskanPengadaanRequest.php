<?php

namespace App\Http\Requests;

use App\Enums\KeputusanPengadaan;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Keputusan Finance/Owner atas pengajuan pengadaan.
 */
class PutuskanPengadaanRequest extends FormRequest
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
            'keputusan' => ['required', Rule::in(KeputusanPengadaan::values())],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'keputusan.required' => 'Keputusan wajib dipilih.',
            'keputusan.in' => 'Keputusan tidak dikenal.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
