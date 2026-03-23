<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create()
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     *
     * Aceita login por email OU por número de processo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required'],
        ], [
            'login.required'    => 'O email ou número de processo é obrigatório.',
            'password.required' => 'A senha é obrigatória.',
        ]);

        $login    = $request->input('login');
        $password = $request->input('password');

        // Determina se o utilizador digitou um email ou número de processo
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'numero_processo';

        // Tenta encontrar o utilizador
        $user = User::where($field, $login)->first();

        // Conta desativada?
        if ($user && ! $user->ativo) {
            throw ValidationException::withMessages([
                'login' => 'Esta conta está desativada. Contacte o administrador.',
            ]);
        }

        // Credenciais incorretas?
        if (! $user || ! Auth::attempt([$field => $login, 'password' => $password], $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'login' => 'As credenciais fornecidas estão incorretas.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}