<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // siapa saja boleh register
    }

    public function rules(): array
    {
        return [
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users,username', 'alpha_dash'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'phone' => ['nullable', 'string', 'max:20'],
            // roles yang dipilih saat register, contoh: ["buyer"] atau ["buyer", "driver"]
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['required', 'string', 'in:buyer,seller,driver'], // admin TIDAK boleh didaftarkan via form publik
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'Username sudah digunakan.',
            'email.unique' => 'Email sudah terdaftar.',
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'roles.*.in' => 'Role tidak valid. Hanya buyer, seller, atau driver yang bisa didaftarkan sendiri.',
        ];
    }
}