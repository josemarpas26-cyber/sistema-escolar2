<?php

namespace App\Exports;

use App\Exports\Sheets\LogsDashboardSummarySheet;
use App\Exports\Sheets\LogsRecentSheet;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LogsDashboardExport implements WithMultipleSheets
{
    public function __construct(private readonly array $dados)
    {
    }

    public function sheets(): array
    {
        return [
            new LogsDashboardSummarySheet($this->dados),
            new LogsRecentSheet($this->dados['logsRecentes']),
        ];
    }
}
