<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class FocusModeController extends Controller
{
    private const CHUNK_SIZE = 500;

    private function assertPodeGerirLote(): void
    {
        if (! auth()->user()?->isAdmin() && ! auth()->user()?->isSecretaria()) {
            abort(403, 'Sem permissão para executar operações em massa.');
        }
    }

    public function matricularAlunos(Request $request)
    {
        $this->checkPermission('users.edit');
        $this->assertPodeGerirLote();

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'turma_id' => ['required', 'integer', 'exists:turmas,id'],
            'data_matricula' => ['nullable', 'date'],
        ]);

        $alunoRoleId = Role::where('name', 'aluno')->value('id');
        $alunoIds = User::query()
            ->where('role_id', $alunoRoleId)
            ->whereIn('id', $validated['user_ids'])
            ->pluck('id')
            ->all();

        if (empty($alunoIds)) {
            return back()->with('error', 'Nenhum aluno válido foi selecionado para matrícula em massa.');
        }

        $turma = Turma::query()->findOrFail($validated['turma_id']);
        $dataMatricula = Carbon::parse($validated['data_matricula'] ?? now())->toDateString();

        DB::transaction(function () use ($alunoIds, $turma, $dataMatricula): void {
            $idsJaMatriculados = DB::table('turma_aluno')
                ->where('turma_id', $turma->id)
                ->whereIn('aluno_id', $alunoIds)
                ->pluck('aluno_id')
                ->all();

            $novos = array_values(array_diff($alunoIds, $idsJaMatriculados));
            $vagasDisponiveis = max(0, $turma->capacidade - $turma->total_alunos);

            if (count($novos) > $vagasDisponiveis) {
                throw ValidationException::withMessages([
                    'turma_id' => 'Capacidade insuficiente para concluir a matrícula em massa. Ajuste a seleção de alunos.',
                ]);
            }

            foreach (array_chunk($alunoIds, self::CHUNK_SIZE) as $chunk) {
                $payload = collect($chunk)
                    ->map(fn (int $alunoId) => [
                        'turma_id' => $turma->id,
                        'aluno_id' => $alunoId,
                        'data_matricula' => $dataMatricula,
                        'status' => 'matriculado',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ])
                    ->all();

                DB::table('turma_aluno')->upsert(
                    $payload,
                    ['turma_id', 'aluno_id'],
                    ['data_matricula', 'status', 'updated_at']
                );
            }
        });

        return back()->with('success', 'Modo Foco aplicado: matrícula em massa concluída com sucesso.');
    }

    public function atualizarStatus(Request $request)
    {
        $this->checkPermission('users.edit');
        $this->assertPodeGerirLote();

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'ativo' => ['required', 'boolean'],
        ]);

        $afetados = 0;

        DB::transaction(function () use ($validated, &$afetados): void {
            foreach (array_chunk($validated['user_ids'], self::CHUNK_SIZE) as $chunk) {
                $afetados += User::query()
                    ->whereIn('id', $chunk)
                    ->update([
                        'ativo' => (bool) $validated['ativo'],
                        'updated_at' => now(),
                    ]);
            }
        });

        return back()->with('success', "Modo Foco aplicado: {$afetados} registo(s) atualizado(s) em lote.");
    }

    public function arquivarUsuarios(Request $request)
    {
        $this->assertPodeGerirLote();

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $ids = array_values(array_diff($validated['user_ids'], [auth()->id()]));

        if (empty($ids)) {
            return back()->with('error', 'Selecione pelo menos um utilizador diferente da sua própria conta.');
        }

        $totalArquivados = 0;

        DB::transaction(function () use ($ids, &$totalArquivados): void {
            User::query()->with(['role', 'turmas'])->whereIn('id', $ids)->chunkById(self::CHUNK_SIZE, function ($users) use (&$totalArquivados) {
                foreach ($users as $user) {
                    ActivityLog::registarDelecao($user);
                    $user->delete();
                    $totalArquivados++;
                }
            });
        });

        return back()->with('success', "Modo Foco aplicado: {$totalArquivados} utilizador(es) arquivado(s) com sucesso.");
    }

    public function importarAlunos(Request $request)
    {
        $this->checkPermission('users.create');
        $this->assertPodeGerirLote();

        $validated = $request->validate([
            'ficheiro' => ['required', 'file', 'mimes:csv,txt,xlsx,xls', 'max:10240'],
        ]);

        $file = $validated['ficheiro'];
        $rows = Excel::toArray([], $file)[0] ?? [];

        if (count($rows) < 2) {
            return back()->with('error', 'Ficheiro sem dados para importação em massa.');
        }

        $headers = collect(array_shift($rows))
            ->map(fn ($header) => Str::of((string) $header)->lower()->ascii()->replace([' ', '-'], '_')->value())
            ->all();

        $alunoRoleId = Role::where('name', 'aluno')->value('id');
        if (! $alunoRoleId) {
            return back()->with('error', 'Perfil de aluno não configurado no sistema.');
        }

        $toInsert = [];
        $errors = [];
        $agora = now();

        foreach ($rows as $index => $row) {
            $linha = $index + 2;
            $registro = [];

            foreach ($headers as $colIndex => $header) {
                $registro[$header] = isset($row[$colIndex]) ? trim((string) $row[$colIndex]) : null;
            }

            if (blank($registro['name'] ?? null) || blank($registro['numero_processo'] ?? null) || blank($registro['bi'] ?? null) || blank($registro['data_nascimento'] ?? null)) {
                $errors[] = "Linha {$linha}: campos obrigatórios ausentes (name, numero_processo, bi, data_nascimento).";
                continue;
            }

            $timestampNascimento = strtotime((string) ($registro['data_nascimento'] ?? ''));
            if ($timestampNascimento === false) {
                $errors[] = "Linha {$linha}: data_nascimento inválida.";
                continue;
            }

            $nascimento = Carbon::createFromTimestamp($timestampNascimento);

            $email = blank($registro['email'] ?? null) ? null : mb_strtolower($registro['email']);

            $toInsert[] = [
                'name' => $registro['name'],
                'email' => $email,
                'email_verified_at' => null,
                'password' => Hash::make((string) $registro['numero_processo']),
                'role_id' => $alunoRoleId,
                'bi' => $registro['bi'],
                'data_nascimento' => $nascimento->toDateString(),
                'genero' => in_array(($registro['genero'] ?? null), ['M', 'F'], true) ? $registro['genero'] : null,
                'telefone' => $registro['telefone'] ?? null,
                'endereco' => $registro['endereco'] ?? null,
                'numero_processo' => $registro['numero_processo'],
                'nome_encarregado' => $registro['nome_encarregado'] ?? null,
                'contacto_encarregado' => $registro['contacto_encarregado'] ?? null,
                'ativo' => true,
                'created_at' => $agora,
                'updated_at' => $agora,
            ];
        }

        if (empty($toInsert)) {
            return back()->with('error', 'Importação cancelada: nenhum registo válido encontrado.');
        }

        DB::transaction(function () use ($toInsert): void {
            foreach (array_chunk($toInsert, self::CHUNK_SIZE) as $chunk) {
                DB::table('users')->insertOrIgnore($chunk);
            }
        });

        $importados = User::query()
            ->whereIn('numero_processo', collect($toInsert)->pluck('numero_processo')->all())
            ->count();

        $ignorados = count($toInsert) - $importados;

        $mensagem = "Modo Foco aplicado: {$importados} aluno(s) importado(s) em massa.";

        if ($ignorados > 0 || ! empty($errors)) {
            $mensagem .= " {$ignorados} registo(s) ignorado(s) por duplicidade ou inconsistência.";
        }

        if (! empty($errors)) {
            $mensagem .= ' Primeiros erros: '.implode(' | ', array_slice($errors, 0, 3));
        }

        return back()->with('success', $mensagem);
    }
}
