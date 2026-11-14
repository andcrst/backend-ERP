<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengaturan toko (CP-5).
 */
class UpdatePengaturanRequest extends FormRequest
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
            'nama_toko' => ['sometimes', 'string', 'max:100'],
            'alamat_toko' => ['nullable', 'string', 'max:500'],
            'telepon_toko' => ['nullable', 'string', 'max:30'],
            'prefix_struk' => ['sometimes', 'string', 'max:10', 'alpha_num'],
            'stok_minimum_default' => ['sometimes', 'integer', 'min:0'],
            'biaya_angkut_default' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_toko.max' => 'Nama toko maksimal 100 karakter.',
            'alamat_toko.max' => 'Alamat maksimal 500 karakter.',
            'telepon_toko.max' => 'Telepon maksimal 30 karakter.',
            'prefix_struk.max' => 'Prefix struk maksimal 10 karakter.',
            'prefix_struk.alpha_num' => 'Prefix struk hanya huruf dan angka.',
            'stok_minimum_default.min' => 'Stok minimum tidak boleh negatif.',
            'biaya_angkut_default.min' => 'Biaya angkut tidak boleh negatif.',
        ];
    }
}
