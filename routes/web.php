<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CursoController;
use App\Http\Controllers\DisciplinaController;
use App\Http\Controllers\TurmaController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\AnoLetivoController;
use App\Http\Controllers\RelatorioController;
use App\Http\Controllers\LogController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Página inicial (redirect para login ou dashboard)
Route::get('/', function () {
    return auth()->check() 
        ? redirect()->route('dashboard') 
        : redirect()->route('login');
});

// Página padrão para erros de autorização
Route::view('/forbidden', 'errors.forbidden')->name('forbidden');

// Rotas protegidas (requerem autenticação)
Route::middleware(['auth'])->group(function () {
    
    // === DASHBOARD ===
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('users/lixeira', [UserController::class, 'lixeira'])
        ->name('users.lixeira');

    Route::post('users/{id}/restore', [UserController::class, 'restore'])
        ->name('users.restore');

    // === USUÁRIOS ===
    Route::resource('users', UserController::class);
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
        ->name('users.toggle-status');

     //  Route::post('users/{user}/restore', [UserController::class, 'restore'])
    //    ->name('users.restore');
    
    Route::get('users/alunos', [UserController::class, 'alunos'])
        ->name('users.alunos');
    Route::get('users/professores', [UserController::class, 'professores'])
        ->name('users.professores');

    // === CURSOS ===
    Route::resource('cursos', CursoController::class);
    Route::post('cursos/{curso}/toggle-status', [CursoController::class, 'toggleStatus'])
        ->name('cursos.toggle-status');

    // === DISCIPLINAS ===
    Route::resource('disciplinas', DisciplinaController::class);
    Route::post('disciplinas/{disciplina}/toggle-status', [DisciplinaController::class, 'toggleStatus'])
        ->name('disciplinas.toggle-status');

    // === TURMAS ===
    Route::resource('turmas', TurmaController::class);
    Route::post('turmas/{turma}/toggle-status', [TurmaController::class, 'toggleStatus'])
        ->name('turmas.toggle-status');
    Route::post('turmas/{turma}/matricular-aluno', [TurmaController::class, 'matricularAluno'])
        ->name('turmas.matricular-aluno');
    Route::delete('turmas/{turma}/alunos/{aluno}', [TurmaController::class, 'removerAluno'])
        ->name('turmas.remover-aluno');
    Route::post('turmas/{turma}/atribuir-professor', [TurmaController::class, 'atribuirProfessor'])
        ->name('turmas.atribuir-professor');
    Route::delete('turmas/{turma}/atribuicoes/{atribuicao}', [TurmaController::class, 'removerProfessor'])
        ->name('turmas.remover-professor');
    Route::post('turmas/{turma}/promover', [TurmaController::class, 'promover'])
        ->name('turmas.promover');

    // === NOTAS ===
    Route::get('notas', [NotaController::class, 'index'])
        ->name('notas.index');
    
    // Index por papel
    Route::get('notas/professor', [NotaController::class, 'professorIndex'])
        ->name('notas.professor-index');
    Route::get('notas/secretaria', [NotaController::class, 'secretariaIndex'])
        ->name('notas.secretaria-index');
    Route::get('notas/aluno', [NotaController::class, 'alunoIndex'])
        ->name('notas.aluno-index');
    
    Route::get('notas/{nota}/edit', [NotaController::class, 'edit'])
        ->name('notas.edit');
    Route::put('notas/{nota}', [NotaController::class, 'update'])
        ->name('notas.update');
    
    // Lançamento por trimestre
    Route::post('notas/trimestre-1', [NotaController::class, 'lancarTrimestre1'])
        ->name('notas.trimestre-1');
    Route::post('notas/trimestre-2', [NotaController::class, 'lancarTrimestre2'])
        ->name('notas.trimestre-2');
    Route::post('notas/trimestre-3', [NotaController::class, 'lancarTrimestre3'])
        ->name('notas.trimestre-3');
    
    Route::post('notas/prova-global', [NotaController::class, 'lancarProvaGlobal'])
    ->name('notas.prova-global');
        Route::post('notas/inicializar-pauta', [NotaController::class, 'inicializarPauta'])
        ->name('notas.inicializar-pauta');
    // Importar CAs
    Route::post('notas/importar-cas', [NotaController::class, 'importarCAs'])
        ->name('notas.importar-cas');
    
    // Finalizar
    Route::post('notas/finalizar', [NotaController::class, 'finalizar'])
        ->name('notas.finalizar');
     Route::post('notas/reabrir', [NotaController::class, 'reabrir'])
        ->name('notas.reabrir');

    // === ANOS LETIVOS ===
    Route::resource('anos-letivos', AnoLetivoController::class)
    ->parameters([
        'anos-letivos' => 'anoLetivo'
    ]);
    Route::post('anos-letivos/{anoLetivo}/encerrar', [AnoLetivoController::class, 'encerrar'])
        ->name('anos-letivos.encerrar');
    Route::post('anos-letivos/{anoLetivo}/reativar', [AnoLetivoController::class, 'reativar'])
        ->name('anos-letivos.reativar');

    // === RELATÓRIOS ===
    Route::get('relatorios', [RelatorioController::class, 'index'])
        ->name('relatorios.index');
    
    // Boletim do aluno
    Route::get('relatorios/boletim/{aluno?}', [RelatorioController::class, 'boletimAluno'])
        ->name('relatorios.boletim');
    
    // Pauta da turma
    Route::get('relatorios/pauta/{turma}/{disciplina?}', [RelatorioController::class, 'pautaTurma'])
        ->name('relatorios.pauta');
    
    // Histórico académico
    Route::get('relatorios/historico/{aluno?}', [RelatorioController::class, 'historicoAcademico'])
        ->name('relatorios.historico');
        Route::get('relatorios/historico-professor/{professor?}', [RelatorioController::class, 'historicoProfessor'])
        ->name('relatorios.historico-professor');
    
    // Consolidado da turma
    Route::get('relatorios/consolidado/{turma}', [RelatorioController::class, 'consolidadoTurma'])
        ->name('relatorios.consolidado');

    // === LOGS ===
    Route::get('logs', [LogController::class, 'index'])
        ->name('logs.index');
    Route::get('logs/dashboard', [LogController::class, 'dashboard'])
        ->name('logs.dashboard');
    Route::get('logs/aluno/{aluno}', [LogController::class, 'porAluno'])
        ->name('logs.aluno');
    Route::get('logs/nota/{nota}', [LogController::class, 'porNota'])
        ->name('logs.nota');
    Route::get('logs/turma/{turma}/disciplina/{disciplina}', [LogController::class, 'porTurmaDisciplina'])
        ->name('logs.turma-disciplina');
    Route::get('logs/usuario/{usuario}', [LogController::class, 'porUsuario'])
        ->name('logs.usuario');
    Route::get('logs/exportar', [LogController::class, 'exportar'])
        ->name('logs.exportar');
});

// Rotas de autenticação
require __DIR__.'/auth.php';