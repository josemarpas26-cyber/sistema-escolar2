<?php

namespace App\Http\Controllers\Auth;

use App\Notifications\EmailAlteradoNotification;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function show()
    {
        $user = Auth::user();

        return view('auth.profile', [
            'user' => $user,
            'canEditProfile' => !($user->isAluno() || $user->isProfessor()),
        ]);
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        if ($user->isAluno() || $user->isProfessor()) {
            abort(403, 'Nao tem permissao para editar informacoes de perfil. Apenas a senha pode ser alterada.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'telefone' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'foto_perfil' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required' => 'O nome e obrigatorio',
            'email.required' => 'O email e obrigatorio',
            'email.email' => 'Insira um email valido',
            'email.unique' => 'Este email ja esta em uso',
            'foto_perfil.image' => 'O arquivo deve ser uma imagem',
            'foto_perfil.max' => 'A imagem nao pode exceder 2MB',
        ]);

        $emailAnterior = $user->email;
        $emailAlterado = array_key_exists('email', $validated) && $validated['email'] !== $emailAnterior;

        if ($request->hasFile('foto_perfil')) {
            if ($user->foto_perfil) {
                Storage::disk('public')->delete($user->foto_perfil);
            }

            $validated['foto_perfil'] = $request->file('foto_perfil')
                ->store('fotos_perfil', 'public');
        }

        $user->update($validated);

        if ($emailAlterado && $user->email) {
            $user->notify(new EmailAlteradoNotification($emailAnterior, $user->email));
        }

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.required' => 'A senha atual e obrigatoria',
            'current_password.current_password' => 'A senha atual esta incorreta',
            'password.required' => 'A nova senha e obrigatoria',
            'password.confirmed' => 'As senhas nao coincidem',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Senha alterada com sucesso!');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->isAluno() || $user->isProfessor()) {
            abort(403, 'Nao tem permissao para deletar a sua conta. Contacte a administracao.');
        }

        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'A senha e obrigatoria',
            'password.current_password' => 'A senha esta incorreta',
        ]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
