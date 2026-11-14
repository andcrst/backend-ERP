<?php

namespace App\Http\Requests;

use App\Enums\UserRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

/**
 * Buat / ubah pengguna sistem (CP-5).
 */
class StoreUserRequest extends FormRequest
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
        $user = $this->route('user');
        $isUpdate = $user !== null;

        return [
            'name' => [Rule::requiredIf(! $isUpdate), 'string', 'max:100'],
            'email' => [
                Rule::requiredIf(! $isUpdate),
                'email',
                'max:150',
                Rule::unique('users', 'email')->ignore($user?->id, 'id'),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'password' => [
                Rule::requiredIf(! $isUpdate),
                'nullable',
                'string',
                Password::min(8),
            ],
            'role' => [Rule::requiredIf(! $isUpdate), Rule::in(UserRole::values())],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama pengguna wajib diisi.',
            'name.max' => 'Nama pengguna maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan pengguna lain.',
            'phone.max' => 'Telepon maksimal 20 karakter.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'role.required' => 'Peran wajib dipilih.',
            'role.in' => 'Peran tidak dikenal. Pilihan: OWNER, FINANCE, GUDANG, KASIR.',
        ];
    }
}
