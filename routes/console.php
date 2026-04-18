<?php

use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;

Artisan::command('backup:database', function () {
    $defaultConnection = config('database.default');
    $connection = config("database.connections.{$defaultConnection}");

    if (! is_array($connection)) {
        $this->error('A conexão de base de dados padrão não está configurada.');
        return self::FAILURE;
    }

    $backupDiskPath = storage_path('app/backups/database');
    File::ensureDirectoryExists($backupDiskPath);

    $timestamp     = now()->format('Y-m-d_His');
    $retentionDays = (int) env('DB_BACKUP_RETENTION_DAYS', 14);
    $cleanupThreshold = now()->subDays(max($retentionDays, 1));

    foreach (File::files($backupDiskPath) as $file) {
        if ($file->getMTime() < $cleanupThreshold->timestamp) {
            File::delete($file->getPathname());
        }
    }

    $driver = $connection['driver'] ?? null;

    // ── SQLite ────────────────────────────────────────────────────────────
    if ($driver === 'sqlite') {
        $sqlitePath = $connection['database'] ?? database_path('database.sqlite');

        if (! File::exists($sqlitePath)) {
            $this->error("Arquivo SQLite não encontrado em: {$sqlitePath}");
            return self::FAILURE;
        }

        $targetFile = "{$backupDiskPath}/{$defaultConnection}_{$timestamp}.sqlite";
        File::copy($sqlitePath, $targetFile);
        $this->info("Backup SQLite criado em: {$targetFile}");
        return self::SUCCESS;
    }

    // ── MySQL via PDO (sem processo externo) ──────────────────────────────
    if ($driver === 'mysql') {
        $database = $connection['database'] ?? null;

        if (! $database) {
            $this->error('Base de dados não configurada.');
            return self::FAILURE;
        }

        $targetFile = "{$backupDiskPath}/{$defaultConnection}_{$timestamp}.sql";

        try {
            $pdo = DB::connection($defaultConnection)->getPdo();
            $output = [];

            $output[] = "-- Backup gerado em: " . now()->toDateTimeString();
            $output[] = "-- Base de dados: {$database}";
            $output[] = "SET FOREIGN_KEY_CHECKS=0;";
            $output[] = "";

            // Obter todas as tabelas
            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $this->line("  → A exportar tabela: {$table}");

                // DROP + CREATE
                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
                $createSQL  = $createStmt['Create Table'] ?? '';

                $output[] = "DROP TABLE IF EXISTS `{$table}`;";
                $output[] = $createSQL . ";";
                $output[] = "";

                // Dados
                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

                if (! empty($rows)) {
                    $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
                    $chunks  = array_chunk($rows, 100);

                    foreach ($chunks as $chunk) {
                        $values = array_map(function (array $row) use ($pdo): string {
                            $escaped = array_map(
                                fn ($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                                $row
                            );
                            return '(' . implode(', ', $escaped) . ')';
                        }, $chunk);

                        $output[] = "INSERT INTO `{$table}` ({$columns}) VALUES";
                        $output[] = implode(",\n", $values) . ";";
                        $output[] = "";
                    }
                }
            }

            // Views
            $views = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'VIEW'")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($views as $view) {
                $createView = $pdo->query("SHOW CREATE VIEW `{$view}`")->fetch(PDO::FETCH_ASSOC);
                $viewSQL    = $createView['Create View'] ?? '';

                if ($viewSQL) {
                    $output[] = "DROP VIEW IF EXISTS `{$view}`;";
                    $output[] = $viewSQL . ";";
                    $output[] = "";
                }
            }

            $output[] = "SET FOREIGN_KEY_CHECKS=1;";

            File::put($targetFile, implode("\n", $output));

            $this->info("Backup criado em: {$targetFile}");
            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Falha ao gerar backup: ' . $e->getMessage());
            return self::FAILURE;
        }
    }

    $this->error("Driver '{$driver}' não suportado para backup automático.");
    return self::FAILURE;

})->purpose('Cria um backup da base de dados em storage/app/backups/database');

Schedule::command('backup:database')
    ->dailyAt(env('DB_BACKUP_SCHEDULE', '02:00'))
    ->withoutOverlapping();