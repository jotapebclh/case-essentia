<?php

namespace App\Http\Requests;

use App\Models\Cliente;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClienteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $cliente = $this->route('cliente');
        $emailUnico = Rule::unique('clientes', 'email')->withoutTrashed();

        if ($cliente instanceof Cliente) {
            $emailUnico->ignore($cliente);
        }

        return [
            'nome' => ['required', 'string', 'max:150'],
            'email' => ['required', 'string', 'email', 'max:255', $emailUnico],
            'telefone' => [
                'required', 'string', 'max:20',
                'regex:/\A(?:\([0-9]{2}\)|[0-9]{2})[ ]?[0-9]{4,5}-?[0-9]{4}\z/',
            ],
            'foto' => [
                $cliente instanceof Cliente ? 'nullable' : 'required',
                'image', 'mimes:jpeg,png,webp', 'max:2048',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'required' => 'O campo :attribute é obrigatório.',
            'string' => 'O campo :attribute deve ser um texto.',
            'nome.max' => 'O nome deve ter no máximo 150 caracteres.',
            'email.email' => 'Informe um e-mail válido.',
            'email.max' => 'O e-mail deve ter no máximo 255 caracteres.',
            'email.unique' => 'Este e-mail já está em uso por um cliente ativo.',
            'telefone.max' => 'O telefone deve ter no máximo 20 caracteres.',
            'telefone.regex' => 'Informe um telefone com DDD e 10 ou 11 dígitos.',
            'foto.image' => 'A foto deve ser uma imagem válida.',
            'foto.mimes' => 'A foto deve ser do tipo JPEG, PNG ou WebP.',
            'foto.max' => 'A foto deve ter no máximo 2 MB.',
            'foto.uploaded' => 'Não foi possível enviar a foto.',
        ];
    }

    public function attributes(): array
    {
        return ['nome' => 'nome', 'email' => 'e-mail', 'telefone' => 'telefone', 'foto' => 'foto'];
    }
}
