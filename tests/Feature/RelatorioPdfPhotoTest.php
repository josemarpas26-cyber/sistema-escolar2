<?php

namespace Tests\Feature;

use App\Models\AnoLetivo;
use App\Models\Curso;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RelatorioPdfPhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_resolve_foto_perfil_pdf_com_fallback_correto(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('fotos_perfil/aluno.jpg', 'foto-fake');

        $alunoComFoto = User::factory()->make([
            'foto_perfil' => 'fotos_perfil/aluno.jpg',
            'genero' => 'M',
        ]);

        $alunaSemFoto = User::factory()->make([
            'foto_perfil' => null,
            'genero' => 'F',
        ]);

        $this->assertSame(
            Storage::disk('public')->path('fotos_perfil/aluno.jpg'),
            $alunoComFoto->foto_perfil_pdf_src
        );

        $this->assertSame(
            public_path('images/default-female.png'),
            $alunaSemFoto->foto_perfil_pdf_src
        );
    }

    public function test_templates_pdf_usam_src_resolvido_sem_duplicar_bloco_da_foto(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('fotos_perfil/aluno.jpg', 'foto-fake');

        $aluno = User::factory()->make([
            'name' => 'Aluno PDF',
            'numero_processo' => '2024001',
            'foto_perfil' => 'fotos_perfil/aluno.jpg',
            'genero' => 'M',
        ]);

        $curso = new Curso(['nome' => 'Informática']);
        $turma = new Turma(['nome' => 'A', 'classe' => '10']);
        $turma->setRelation('curso', $curso);

        $anoLetivo = new AnoLetivo(['nome' => '2025/2026']);

        $htmlBoletim = view('relatorios.pdf.boletim', [
            'aluno' => $aluno,
            'turma' => $turma,
            'anoLetivo' => $anoLetivo,
            'notas' => collect(),
            'mediaGeral' => 0,
            'aprovacoes' => 0,
            'reprovacoes' => 0,
            'trimestre' => '1',
        ])->render();

        $htmlHistorico = view('relatorios.pdf.historico', [
            'aluno' => $aluno,
            'historico' => collect(),
        ])->render();

        $this->assertStringContainsString($aluno->foto_perfil_pdf_src, $htmlBoletim);
        $this->assertStringContainsString($aluno->foto_perfil_pdf_src, $htmlHistorico);
        $this->assertStringContainsString(public_path('images/logo1.png'), $htmlHistorico);
        $this->assertSame(1, substr_count($htmlHistorico, 'class="photo-box"'));
    }
}
