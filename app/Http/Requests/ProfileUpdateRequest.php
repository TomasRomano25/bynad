<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'   => ['required', 'string', 'max:255'],
            'email'  => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($this->user()->id)],
            'age'    => ['nullable', 'integer', 'min:1', 'max:120'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required'  => 'El nombre es obligatorio.',
            'email.required' => 'El email es obligatorio.',
            'email.email'    => 'Ingresa un email valido.',
            'email.unique'   => 'Ese email ya esta en uso por otra cuenta.',
            'age.integer'    => 'La edad debe ser un numero.',
            'age.min'        => 'La edad no puede ser menor a 1.',
            'age.max'        => 'La edad no puede ser mayor a 120.',
            'avatar.image'   => 'El archivo debe ser una imagen.',
            'avatar.max'     => 'La imagen no puede superar 2MB.',
        ];
    }
}
