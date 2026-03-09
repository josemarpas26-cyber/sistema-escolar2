<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Turma;

class UserController extends Controller
{
    /**
     * Listar usuários
     */
    public function index(Request $request)
    {
        $this->checkPermission('users.view');

        $query = User::with('role')->latest();

        // Filtros
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('numero_processo', 'like', "%{$search}%")
                  ->orWhere('bi', 'like', "%{$search}%");
            });
        }

        if ($request->filled('ativo')) {
            $query->where('ativo', $request->boolean('ativo'));
        }

        $users = $query->paginate(20);
        $roles = Role::all();

        return view('users.index', compact('users', 'roles'));
    }

    /**
     * Formulário de criação
     */
    public function create()
    {
        $this->checkPermission('users.create');
        
        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    /**
     * Salvar novo usuário
     */
    public function store(Request $request)
    {
        $this->checkPermission('users.create');

        $selectedRole = Role::find($request->input('role_id'));
         $shouldGeneratePassword = $request->boolean('auto_password')
            || optional($selectedRole)->name === 'professor';

        $passwordRules = $request->boolean('auto_password')
            ? ['nullable']
            : ['required', 'string', 'min:8', 'confirmed'];

         if (optional($selectedRole)->name === 'professor') {
            $passwordRules = ['nullable'];
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => $passwordRules,
            'role_id' => 'required|exists:roles,id',
            'auto_password' => 'nullable|boolean',
            'bi' => 'nullable|string|unique:users,bi',
            'data_nascimento' => 'nullable|date',
            'genero' => 'nullable|in:M,F',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'nullable|string|max:255',
            'foto_perfil' => 'nullable|image|max:2048',
            'numero_processo' => 'nullable|string|unique:users,numero_processo',
            'nome_encarregado' => 'nullable|string|max:255',
            'contacto_encarregado' => 'nullable|string|max:20',
        ]);

             $generatedPassword = null;

        if ($shouldGeneratePassword) {
            $generatedPassword = Str::password(12);
            $validated['password'] = $generatedPassword;
        }

        $validated['password'] = Hash::make($validated['password']);
        unset($validated['auto_password']);

        // Upload de foto
        if ($request->hasFile('foto_perfil')) {
            $validated['foto_perfil'] = $request->file('foto_perfil')
                ->store('fotos_perfil', 'public');
        }

        $user = User::create($validated);

          $successMessage = 'Usuário criado com sucesso!';

        if ($generatedPassword) {
            $successMessage .= " Senha provisória gerada: {$generatedPassword}";
        }

        return redirect()
            ->route('users.show', $user)
            ->with('success', $successMessage);
    }

    /**
     * Exibir usuário
     */
    public function show(User $user)
    {
        $this->checkPermission('users.view');

        $user->load(['role', 'turmas.curso', 'atribuicoes.turma', 'atribuicoes.disciplina']);

        return view('users.show', compact('user'));
    }

    /**
     * Formulário de edição
     */
    public function edit(User $user)
    {
        $this->checkPermission('users.edit');

        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    /**
     * Atualizar usuário
     */
    public function update(Request $request, User $user)
    {
        $this->checkPermission('users.edit');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:6|confirmed',
            'role_id' => 'required|exists:roles,id',
            'bi' => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
            'data_nascimento' => 'nullable|date',
            'genero' => 'nullable|in:M,F',
            'telefone' => 'nullable|string|max:20',
            'endereco' => 'nullable|string|max:255',
            'foto_perfil' => 'nullable|image|max:2048',
            'numero_processo' => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
            'nome_encarregado' => 'nullable|string|max:255',
            'contacto_encarregado' => 'nullable|string|max:20',
            'ativo' => 'boolean',
        ]);

        // Atualizar senha apenas se fornecida
        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        // Upload de nova foto
        if ($request->hasFile('foto_perfil')) {
            // Deletar foto antiga
            if ($user->foto_perfil) {
                Storage::disk('public')->delete($user->foto_perfil);
            }
            
            $validated['foto_perfil'] = $request->file('foto_perfil')
                ->store('fotos_perfil', 'public');
        }

        $user->update($validated);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Deletar usuário (soft delete)
     */
    public function destroy(User $user)
    {
        $this->checkPermission('users.delete');

        // Não permitir deletar o próprio usuário
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Você não pode deletar seu próprio usuário!');
        }

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', 'Usuário deletado com sucesso!');
    }

    /**
     * Restaurar usuário deletado
     */
    public function restore($id)
    {
        $this->checkPermission('users.create');

        $user = User::withTrashed()->findOrFail($id);
        $user->restore();

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Usuário restaurado com sucesso!');
    }

    /**
     * Ativar/Desativar usuário
     */
    public function toggleStatus(User $user)
    {
        $this->checkPermission('users.edit');

        $user->update(['ativo' => !$user->ativo]);

        $status = $user->ativo ? 'ativado' : 'desativado';
        
        return back()->with('success', "Usuário {$status} com sucesso!");
    }

    /**
     * Listar apenas alunos
     */
    public function alunos(Request $request)
{
    $this->checkPermission('users.view');

    $query = User::alunos()->with(['role', 'turmas.curso']);

    if ($request->filled('search')) {
        $search = $request->search;
        $query->where(function($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('numero_processo', 'like', "%{$search}%");
        });
    }

    // 👇 FILTRO POR TURMA (se estiver usando no select)
    if ($request->filled('turma')) {
        $query->whereHas('turmas', function ($q) use ($request) {
            $q->where('turma_id', $request->turma);
        });
    }

    // 👇 FILTRO POR STATUS (já que seu form tem isso)
    if ($request->filled('status')) {
        $query->where('ativo', $request->status === 'ativo');
    }

    $alunos = $query->paginate(20);

    // 👇 ESSA LINHA ESTAVA FALTANDO
    $turmas = Turma::orderBy('nome')->get();

    return view('users.alunos', compact('alunos', 'turmas'));
}


    /**
     * Listar apenas professores
     */
    public function professores(Request $request)
    {
        $this->checkPermission('users.view');

        $query = User::professores()->with(['role', 'atribuicoes.turma', 'atribuicoes.disciplina']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where('name', 'like', "%{$search}%");
        }

        $professores = $query->paginate(20);

        return view('users.professores', compact('professores'));
    }
}
