<?php

namespace Tests\Unit;

use App\Models\NotaLog;
use Tests\TestCase;

class NotaLogFormattingTest extends TestCase
{
    public function test_resumo_alteracao_de_avaliacao_continua_fica_legivel(): void
    {
        $log = new NotaLog([
            'acao' => 'edicao',
            'campo_alterado' => 'avaliacao_continua',
            'valor_anterior' => json_encode([
                'descricao' => 'AC 1.1',
                'valor' => '15.50',
                'data_avaliacao' => '2026-03-24T00:00:00.000000Z',
            ], JSON_UNESCAPED_UNICODE),
            'valor_novo' => json_encode([
                'descricao' => 'AC 1.1',
                'valor' => '12.00',
                'data_avaliacao' => '2026-03-25',
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $this->assertSame(
            'AC 1.1 | 15,50 | 24/03/2026 → AC 1.1 | 12,00 | 25/03/2026',
            $log->resumo_alteracao
        );
    }

    public function test_resumo_alteracao_de_avaliacao_sem_descricao_usa_texto_padrao(): void
    {
        $log = new NotaLog([
            'acao' => 'edicao',
            'campo_alterado' => 'avaliacao_continua',
            'valor_anterior' => null,
            'valor_novo' => json_encode([
                'descricao' => '',
                'valor' => 10,
                'data_avaliacao' => null,
            ], JSON_UNESCAPED_UNICODE),
        ]);

        $this->assertSame('— → Sem descrição | 10,00 | Sem data', $log->resumo_alteracao);
    }

    public function test_descricao_acao_de_avaliacao_continua_segue_acao_base_para_filtros(): void
    {
        $logCriacao = new NotaLog(['acao' => 'criacao', 'campo_alterado' => 'avaliacao_continua']);
        $logEdicao = new NotaLog(['acao' => 'edicao', 'campo_alterado' => 'avaliacao_continua']);
        $logExclusao = new NotaLog(['acao' => 'exclusao', 'campo_alterado' => 'avaliacao_continua']);

        $this->assertSame('Criou avaliação', $logCriacao->descricao_acao);
        $this->assertSame('Editou avaliação', $logEdicao->descricao_acao);
        $this->assertSame('Removeu avaliação', $logExclusao->descricao_acao);
    }

    public function test_descricao_acao_de_avaliacao_continua_legada_nao_fica_como_desconhecida(): void
    {
        $logCriacaoLegado = new NotaLog(['acao' => 'avaliacao_continua_criada']);
        $logEdicaoLegado = new NotaLog(['acao' => 'avaliacao_continua_editada']);
        $logExclusaoLegado = new NotaLog(['acao' => 'avaliacao_continua_removida']);

        $this->assertSame('Criou avaliação', $logCriacaoLegado->descricao_acao);
        $this->assertSame('Editou avaliação', $logEdicaoLegado->descricao_acao);
        $this->assertSame('Removeu avaliação', $logExclusaoLegado->descricao_acao);
    }
}
