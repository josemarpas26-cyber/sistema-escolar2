<?php

namespace App\Http\Controllers\Auth;

use App\Notifications\EmailAlteradoNotification;
use App\Http\Controllers\Controller;
use App\Support\ProfilePhotoStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
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
            abort(403, 'Não tem permissão para editar as informações de perfil. Apenas a senha pode ser alterada.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email,' . $user->id],
            'telefone' => ['nullable', 'string', 'max:20'],
            'endereco' => ['nullable', 'string', 'max:255'],
            'foto_perfil' => ['nullable', 'image', 'max:2048'],
        ], [
            'name.required' => 'O nome é obrigatório.',
            'email.required' => 'O email é obrigatório.',
            'email.email' => 'Insira um email válido.',
            'email.unique' => 'Este email já está em uso.',
            'foto_perfil.image' => 'O ficheiro deve ser uma imagem.',
            'foto_perfil.max' => 'A imagem não pode exceder 2 MB.',
        ]);

        $emailAnterior = $user->email;
        $emailAlterado = array_key_exists('email', $validated) && $validated['email'] !== $emailAnterior;

        if ($request->hasFile('foto_perfil')) {
            ProfilePhotoStorage::delete($user->foto_perfil);
            $validated['foto_perfil'] = ProfilePhotoStorage::store($request->file('foto_perfil'));
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->telefone = $validated['telefone'] ?? null;
        $user->endereco = $validated['endereco'] ?? null;

        if (isset($validated['foto_perfil'])) {
            $user->foto_perfil = $validated['foto_perfil'];
        }

        $user->save();

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
            'current_password.required' => 'A senha atual é obrigatória.',
            'current_password.current_password' => 'A senha atual está incorreta.',
            'password.required' => 'A nova senha é obrigatória.',
            'password.confirmed' => 'As senhas não coincidem.',
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
            abort(403, 'Não tem permissão para eliminar a sua conta. Contacte a administração.');
        }

        $request->validate([
            'password' => ['required', 'current_password'],
        ], [
            'password.required' => 'A senha é obrigatória.',
            'password.current_password' => 'A senha está incorreta.',
        ]);

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
