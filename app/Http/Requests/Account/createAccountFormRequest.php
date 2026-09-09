<?php

namespace App\Http\Requests\Account;

use App\DTO\Account\createAccountDTO;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class createAccountFormRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            "usuario_id" => "required|integer|exists:usuarios,id",
            "tipo" => "sometimes|required|string",
            "moneda" => "sometimes|required|string"
        ];
    }
    public function messages(): array
    {
        return [
            // usuario_id
            'usuario_id.required' => 'El ID del usuario es obligatorio.',
            'usuario_id.integer' => 'El ID del usuario debe ser un número entero.',
            'usuario_id.exists' => 'El usuario seleccionado no existe.',
            'usuario_id.unique' => 'Este usuario ya tiene una cuenta asociada.',

            // tipo
            'tipo.required' => 'El tipo de cuenta es obligatorio.',
            'tipo.string' => 'El tipo de cuenta debe ser texto.',
            'tipo.in' => 'El tipo de cuenta debe ser "ahorro" o "corriente".',

            // moneda
            'moneda.required' => 'La moneda es obligatoria.',
            'moneda.string' => 'La moneda debe ser texto.',
            'moneda.in' => 'La moneda debe ser "ARS" o "USD".',
        ];
    }


    public function toDTO(): createAccountDTO
    {
        return new createAccountDTO(
            usuario_id: $this->input('usuario_id'),
            tipo: $this->input('tipo'),
            moneda: $this->input('moneda'),
        );
    }
}
