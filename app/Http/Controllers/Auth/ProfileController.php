<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * Exibir perfil do utilizador autenticado.
     */
    public function show()
    {
        $user = Auth::user();

        return view('auth.profile', [
            'user' => $user,
            'canEditProfile' => !($user->isAluno() || $user->isProfessor()),
        ]);
    }
    
    /**
     * Exibir formulário dedicado para alteração de senha.
     */
    public function editPassword()
    {
        return view('profile.senha');
    }

    /**
     * Atualizar informações do perfil.
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        if ($user->isAluno() || $user->isProfessor()) {
            abort(403, 'Não tem permissão para editar informações de perfil. Apenas a senha pode ser alterada.');
        }

        $validated = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'email'       => ['required', 'email', 'unique:users,email,' . $user->id],
            'telefone'    => ['nullable', 'string', 'max:20'],
            'endereco'    => ['nullable', 'string', 'max:255'],
            'foto_perfil' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required'       => 'O nome é obrigatório',
            'email.required'      => 'O email é obrigatório',
            'email.email'         => 'Insira um email válido',
            'email.unique'        => 'Este email já está em uso',
            'foto_perfil.image'   => 'O arquivo deve ser uma imagem',
            'foto_perfil.max'     => 'A imagem não pode exceder 2MB',
        ]);

        if ($request->hasFile('foto_perfil')) {
            if ($user->foto_perfil) {
                Storage::disk('public')->delete($user->foto_perfil);
            }
            $validated['foto_perfil'] = $request->file('foto_perfil')
                ->store('fotos_perfil', 'public');
        }

        $user->update($validated);

        return back()->with('success', 'Perfil atualizado com sucesso!');
    }

    /**
     * Alterar senha.
     */
    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', Password::defaults(), 'confirmed'],
        ], [
            'current_password.required'       => 'A senha atual é obrigatória',
            'current_password.current_password' => 'A senha atual está incorreta',
            'password.required'               => 'A nova senha é obrigatória',
            'password.confirmed'              => 'As senhas não coincidem',
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return back()->with('success', 'Senha alterada com sucesso!');
    }

    /**
     * Deletar conta — APENAS ADM e Secretária podem fazer isto.
     * Alunos e Professores NÃO podem auto-deletar.
     */
    public function destroy(Request $request)
    {
        $user = $request->user();

        // Bloquear alunos e professores
        if ($user->isAluno() || $user->isProfessor()) {
            abort(403, 'Não tem permissão para deletar a sua conta. Contacte a administração.');
        }

        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required'         => 'A senha é obrigatória',
            'password.current_password' => 'A senha está incorreta',
        ]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}