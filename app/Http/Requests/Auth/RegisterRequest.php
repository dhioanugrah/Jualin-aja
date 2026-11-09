<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $domain = config('jualin.email_domain');

        return [
            'name' => ['required', 'string', 'max:100'],
            'nim' => ['required', 'regex:/^\d{8,12}$/', 'unique:'.User::class],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'ends_with:@'.$domain, 'unique:'.User::class],
            'whatsapp' => ['required', 'regex:/^628\d{7,11}$/'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.ends_with' => 'Gunakan email kampus yang berakhiran @'.config('jualin.email_domain').'.',
            'nim.regex' => 'NIM terdiri dari 8–12 angka.',
            'whatsapp.regex' => 'Nomor WhatsApp tidak valid. Contoh: 081234567890.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => str($this->string('email'))->trim()->lower()->toString(),
            'whatsapp' => User::normalizeWhatsapp($this->string('whatsapp')->toString()),
        ]);
    }
}
