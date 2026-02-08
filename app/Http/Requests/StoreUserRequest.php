<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->user()->role->hasPermission('users.create');
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'bi' => 'nullable|string|unique:users,bi',
            'data_nascimento' => 'nullable|date|before:today',
            'genero' => 'nullable|in:M,F',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'nullable|string|max:255',
            'foto_perfil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'numero_processo' => 'nullable|string|unique:users,numero_processo',
            'nome_encarregado' => 'nullable|string|max:255',
            'contacto_encarregado' => 'nullable|string|max:20',
            'ativo' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'O nome é obrigatório',
            'email.required' => 'O email é obrigatório',
            'email.email' => 'Email inválido',
            'email.unique' => 'Este email já está em uso',
            'password.required' => 'A senha é obrigatória',
            'password.min' => 'A senha deve ter no mínimo 6 caracteres',
            'password.confirmed' => 'As senhas não coincidem',
            'role_id.required' => 'O papel do usuário é obrigatório',
            'bi.unique' => 'Este BI já está cadastrado',
            'data_nascimento.before' => 'A data de nascimento deve ser anterior a hoje',
            'genero.in' => 'Gênero inválido',
            'foto_perfil.image' => 'O arquivo deve ser uma imagem',
            'foto_perfil.max' => 'A imagem não pode exceder 2MB',
            'numero_processo.unique' => 'Este número de processo já está cadastrado',
        ];
    }
}
