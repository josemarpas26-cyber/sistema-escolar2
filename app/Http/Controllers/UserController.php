<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use App\Models\Turma;

class UserController extends Controller
{
    // ── Helpers de autorização ───────────────────────────────────────────────

    /**
     * Apenas ADM e Secretária podem gerir utilizadores.
     */
    private function assertPodeGerir(): void
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isSecretaria()) {
            abort(403, 'Sem permissão para gerir utilizadores.');
        }
    }

    /**
     * Apenas ADM pode executar ações destrutivas (delete/restore).
     */
    private function assertPodeDestruir(): void
    {
        if (!auth()->user()->isAdmin() && !auth()->user()->isSecretaria()) {
            abort(403, 'Apenas administradores e secretaria podem deletar utilizadores.');
        }
    }

    // ── CRUD ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $this->checkPermission('users.view');

        $query = User::with('role')->latest();

        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
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

    public function create()
    {
        $this->checkPermission('users.create');

        $roles = Role::all();
        return view('users.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $this->checkPermission('users.create');

        $selectedRole = Role::find($request->input('role_id'));
        $shouldGeneratePassword = $request->boolean('auto_password')
            || optional($selectedRole)->name === 'professor';

        $passwordRules = $shouldGeneratePassword
            ? ['nullable']
            : ['required', 'string', 'min:8', 'confirmed'];

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:users,email',
            'password'              => $passwordRules,
            'role_id'               => 'required|exists:roles,id',
            'auto_password'         => 'nullable|boolean',
            'bi'                    => 'nullable|string|unique:users,bi',
            'data_nascimento'       => 'nullable|date',
            'genero'                => 'nullable|in:M,F',
            'telefone'              => 'nullable|string|max:20',
            'endereco'              => 'nullable|string|max:255',
            'foto_perfil'           => 'nullable|image|max:2048',
            'numero_processo'       => 'nullable|string|unique:users,numero_processo',
            'nome_encarregado'      => 'nullable|string|max:255',
            'contacto_encarregado'  => 'nullable|string|max:20',
        ]);

        $generatedPassword = null;
        if ($shouldGeneratePassword) {
            $generatedPassword = Str::password(12);
            $validated['password'] = $generatedPassword;
        }

        $validated['password'] = Hash::make($validated['password']);
        unset($validated['auto_password']);

        if ($request->hasFile('foto_perfil')) {
            $validated['foto_perfil'] = $request->file('foto_perfil')
                ->store('fotos_perfil', 'public');
        }

        $user = User::create($validated);

        $successMessage = 'Utilizador criado com sucesso!';
        if ($generatedPassword) {
            $successMessage .= " Senha provisória: {$generatedPassword} — anote agora, não será exibida novamente.";
        }

        return redirect()
            ->route('users.show', $user)
            ->with('success', $successMessage);
    }

    public function show(User $user)
    {
        $this->checkPermission('users.view');

        $user->load(['role', 'turmas.curso', 'atribuicoes.turma', 'atribuicoes.disciplina']);

        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->checkPermission('users.edit');

        $roles = Role::all();
        return view('users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $this->checkPermission('users.edit');

        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'email'                 => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'password'              => 'nullable|string|min:6|confirmed',
            'role_id'               => 'required|exists:roles,id',
            'bi'                    => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
            'data_nascimento'       => 'nullable|date',
            'genero'                => 'nullable|in:M,F',
            'telefone'              => 'nullable|string|max:20',
            'endereco'              => 'nullable|string|max:255',
            'foto_perfil'           => 'nullable|image|max:2048',
            'numero_processo'       => ['nullable', 'string', Rule::unique('users')->ignore($user->id)],
            'nome_encarregado'      => 'nullable|string|max:255',
            'contacto_encarregado'  => 'nullable|string|max:20',
            'ativo'                 => 'boolean',
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        if ($request->hasFile('foto_perfil')) {
            if ($user->foto_perfil) {
                Storage::disk('public')->delete($user->foto_perfil);
            }
            $validated['foto_perfil'] = $request->file('foto_perfil')
                ->store('fotos_perfil', 'public');
        }

        $user->update($validated);

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Utilizador atualizado com sucesso!');
    }

    /**
     * Soft delete — apenas ADM e Secretária.
     * Ninguém pode deletar a si mesmo.
     * ADM não pode ser deletado pela Secretária.
     */
    public function destroy(User $user)
    {
        $this->assertPodeDestruir();

        if ($user->id === auth()->id()) {
            return back()->with('error', 'Não pode deletar o seu próprio utilizador!');
        }

        // Secretária não pode deletar ADM
        if ($user->isAdmin() && !auth()->user()->isAdmin()) {
            abort(403, 'Secretária não pode deletar administradores.');
        }

        // Guardar snapshot antes de deletar
        ActivityLog::registarDelecao($user);

        $user->delete();

        return redirect()
            ->route('users.index')
            ->with('success', "Utilizador {$user->name} deletado. Pode ser restaurado na Lixeira.");
    }

    /**
     * Listagem de utilizadores deletados — apenas ADM.
     */
    public function lixeira(Request $request)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Apenas administradores podem aceder à lixeira.');
        }

        $query = User::onlyTrashed()->with('role')->latest('deleted_at');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(20);

        return view('users.lixeira', compact('users'));
    }

    /**
     * Restaurar utilizador deletado — apenas ADM.
     * As matrículas/pivots não são afetados pelo soft delete, ficam intactos.
     */
    public function restore($id)
    {
        if (!auth()->user()->isAdmin()) {
            abort(403, 'Apenas administradores podem restaurar utilizadores.');
        }

        $user = User::withTrashed()->findOrFail($id);

        if (!$user->trashed()) {
            return back()->with('error', 'Este utilizador não está na lixeira.');
        }

        $user->restore();

        ActivityLog::registarRestauracao($user);

        return redirect()
            ->route('users.show', $user)
            ->with('success', "Utilizador {$user->name} restaurado com sucesso!");
    }

    public function toggleStatus(User $user)
    {
        $this->checkPermission('users.edit');

        $user->update(['ativo' => !$user->ativo]);

        $status = $user->ativo ? 'ativado' : 'desativado';

        return back()->with('success', "Utilizador {$status} com sucesso!");
    }

    // ── Listagens especializadas ─────────────────────────────────────────────

    public function alunos(Request $request)
    {
        $this->checkPermission('users.view');

        $query = User::alunos()->with(['role', 'turmas.curso']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('numero_processo', 'like', "%{$search}%");
            });
        }

        if ($request->filled('turma')) {
            $query->whereHas('turmas', function ($q) use ($request) {
                $q->where('turma_id', $request->turma);
            });
        }

        if ($request->filled('status')) {
            $query->where('ativo', $request->status === 'ativo');
        }

        $alunos = $query->paginate(20);
        $turmas = Turma::orderBy('nome')->get();

        // IDs das turmas que o professor logado lecciona (para filtro de boletim)
        $turmasProfesor = collect();
        if (auth()->user()->isProfessor()) {
            $turmasProfesor = auth()->user()
                ->atribuicoes()
                ->pluck('turma_id')
                ->unique();
        }

        return view('users.alunos', compact('alunos', 'turmas', 'turmasProfesor'));
    }

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