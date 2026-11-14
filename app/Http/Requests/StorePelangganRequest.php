<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Master pelanggan (D-03=A). Alamat wajib bila diminta.
 */
class StorePelangganRequest extends FormRequest
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
        $pelanggan = $this->route('pelanggan');
        $isUpdate = $pelanggan !== null;

        return [
            'nama_pelanggan' => [Rule::requiredIf(! $isUpdate), 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string', 'max:1000'],
            'catatan' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_pelanggan.required' => 'Nama pelanggan wajib diisi.',
            'nama_pelanggan.max' => 'Nama pelanggan maksimal 100 karakter.',
            'telepon.max' => 'Telepon maksimal 20 karakter.',
            'alamat.max' => 'Alamat maksimal 1000 karakter.',
        ];
    }
}
