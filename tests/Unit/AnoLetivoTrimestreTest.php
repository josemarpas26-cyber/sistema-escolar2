<?php

namespace Tests\Unit;

use App\Models\AnoLetivo;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class AnoLetivoTrimestreTest extends TestCase
{
    public function test_ano_letivo_curto_entra_no_terceiro_trimestre_antes_do_ultimo_dia(): void
    {
        Carbon::setTestNow('2026-04-25 10:00:00');

        try {
            $anoLetivo = new AnoLetivo([
                'data_inicio' => '2026-04-20',
                'data_fim' => '2026-04-26',
            ]);

            $this->assertSame('2026-04-23', $anoLetivo->inicioDoTrimestre(2)?->toDateString());
            $this->assertSame('2026-04-25', $anoLetivo->inicioDoTrimestre(3)?->toDateString());
            $this->assertSame(3, $anoLetivo->trimestreNaData());
        } finally {
            Carbon::setTestNow();
        }
    }
}
