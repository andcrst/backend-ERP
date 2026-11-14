<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Master supplier (D-03=A).
 */
class StoreSupplierRequest extends FormRequest
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
        $supplier = $this->route('supplier');
        $isUpdate = $supplier !== null;

        return [
            'nama_supplier' => [
                Rule::requiredIf(! $isUpdate),
                'string',
                'max:100',
                Rule::unique('supplier', 'nama_supplier')->ignore($supplier?->id_supplier, 'id_supplier'),
            ],
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
            'nama_supplier.required' => 'Nama supplier wajib diisi.',
            'nama_supplier.unique' => 'Nama supplier sudah terdaftar.',
            'nama_supplier.max' => 'Nama supplier maksimal 100 karakter.',
            'telepon.max' => 'Telepon maksimal 20 karakter.',
            'alamat.max' => 'Alamat maksimal 1000 karakter.',
            'catatan.max' => 'Catatan maksimal 1000 karakter.',
        ];
    }
}
