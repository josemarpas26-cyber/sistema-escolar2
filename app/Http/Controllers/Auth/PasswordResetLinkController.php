<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class PasswordResetLinkController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'O email é obrigatório.',
            'email.email'    => 'Insira um email válido.',
        ]);

        // Verifica se o utilizador existe e tem email
        $user = User::where('email', $request->email)->first();

        if (! $user || ! $user->email) {
            throw ValidationException::withMessages([
                'email' => 'Não encontrámos nenhum utilizador com este email. '
                         . 'Se entras com número de processo, contacta o administrador para repor a senha.',
            ]);
        }

        $status = Password::sendResetLink($request->only('email'));

        if ($status == Password::RESET_LINK_SENT) {
            return back()->with('success', 'Link de recuperação enviado para o seu email!');
        }

        throw ValidationException::withMessages([
            'email' => 'Não foi possível enviar o link. Tenta novamente.',
        ]);
    }
}