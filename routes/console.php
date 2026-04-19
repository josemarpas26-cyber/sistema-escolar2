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

    // Limpa todos os backups anteriores, mantendo apenas o mais recente
    $existingFiles = collect(File::files($backupDiskPath))
        ->sortByDesc(fn($f) => $f->getMTime())
        ->skip(1);

    foreach ($existingFiles as $file) {
        File::delete($file->getPathname());
    }

    $timestamp = now()->format('Y-m-d_His');
    $driver    = $connection['driver'] ?? null;

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

    // ── MySQL via PDO ─────────────────────────────────────────────────────
    if ($driver === 'mysql') {
        $database = $connection['database'] ?? null;

        if (! $database) {
            $this->error('Base de dados não configurada.');
            return self::FAILURE;
        }

        $targetFile = "{$backupDiskPath}/{$defaultConnection}_{$timestamp}.sql";

        try {
            $pdo    = DB::connection($defaultConnection)->getPdo();
            $output = [];

            $output[] = "-- Backup gerado em: " . now()->toDateTimeString();
            $output[] = "-- Base de dados: {$database}";
            $output[] = "SET FOREIGN_KEY_CHECKS=0;";
            $output[] = "";

            $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $this->line("  → A exportar tabela: {$table}");

                $createStmt = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
                $createSQL  = $createStmt['Create Table'] ?? '';

                $output[] = "DROP TABLE IF EXISTS `{$table}`;";
                $output[] = $createSQL . ";";
                $output[] = "";

                $rows = $pdo->query("SELECT * FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);

                if (! empty($rows)) {
                    $columns = '`' . implode('`, `', array_keys($rows[0])) . '`';
                    $chunks  = array_chunk($rows, 100);

                    foreach ($chunks as $chunk) {
                        $values = array_map(function (array $row) use ($pdo): string {
                            $escaped = array_map(
                                fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
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

    // ── PostgreSQL via PDO ────────────────────────────────────────────────
    if ($driver === 'pgsql') {
        $database = $connection['database'] ?? null;
        $schema   = $connection['search_path'] ?? $connection['schema'] ?? 'public';

        if (! $database) {
            $this->error('Base de dados não configurada.');
            return self::FAILURE;
        }

        $targetFile = "{$backupDiskPath}/{$defaultConnection}_{$timestamp}.sql";

        try {
            $pdo    = DB::connection($defaultConnection)->getPdo();
            $output = [];

            $output[] = "-- Backup gerado em: " . now()->toDateTimeString();
            $output[] = "-- Base de dados: {$database}";
            $output[] = "SET session_replication_role = replica;"; // desativa FK checks no pgsql
            $output[] = "";

            // Obter todas as tabelas do schema
            $tables = $pdo->query("
                SELECT tablename FROM pg_tables
                WHERE schemaname = '{$schema}'
                ORDER BY tablename
            ")->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $this->line("  → A exportar tabela: {$table}");

                // Obter colunas
                $columns = $pdo->query("
                    SELECT column_name, data_type, is_nullable, column_default
                    FROM information_schema.columns
                    WHERE table_schema = '{$schema}' AND table_name = '{$table}'
                    ORDER BY ordinal_position
                ")->fetchAll(PDO::FETCH_ASSOC);

                // Construir CREATE TABLE
                $colDefs = [];
                foreach ($columns as $col) {
                    $def  = "    \"{$col['column_name']}\" {$col['data_type']}";
                    if ($col['column_default'] !== null) {
                        $def .= " DEFAULT {$col['column_default']}";
                    }
                    if ($col['is_nullable'] === 'NO') {
                        $def .= " NOT NULL";
                    }
                    $colDefs[] = $def;
                }

                // Obter primary keys
                $pks = $pdo->query("
                    SELECT kcu.column_name
                    FROM information_schema.table_constraints tc
                    JOIN information_schema.key_column_usage kcu
                        ON tc.constraint_name = kcu.constraint_name
                        AND tc.table_schema = kcu.table_schema
                    WHERE tc.constraint_type = 'PRIMARY KEY'
                        AND tc.table_schema = '{$schema}'
                        AND tc.table_name = '{$table}'
                    ORDER BY kcu.ordinal_position
                ")->fetchAll(PDO::FETCH_COLUMN);

                if (! empty($pks)) {
                    $pkList   = implode('", "', $pks);
                    $colDefs[] = "    PRIMARY KEY (\"{$pkList}\")";
                }

                $output[] = "DROP TABLE IF EXISTS \"{$schema}\".\"{$table}\" CASCADE;";
                $output[] = "CREATE TABLE \"{$schema}\".\"{$table}\" (";
                $output[] = implode(",\n", $colDefs);
                $output[] = ");";
                $output[] = "";

                // Dados
                $rows = $pdo->query("SELECT * FROM \"{$schema}\".\"{$table}\"")->fetchAll(PDO::FETCH_ASSOC);

                if (! empty($rows)) {
                    $colNames = '"' . implode('", "', array_keys($rows[0])) . '"';
                    $chunks   = array_chunk($rows, 100);

                    foreach ($chunks as $chunk) {
                        $values = array_map(function (array $row) use ($pdo): string {
                            $escaped = array_map(
                                fn($v) => $v === null ? 'NULL' : $pdo->quote((string) $v),
                                $row
                            );
                            return '(' . implode(', ', $escaped) . ')';
                        }, $chunk);

                        $output[] = "INSERT INTO \"{$schema}\".\"{$table}\" ({$colNames}) VALUES";
                        $output[] = implode(",\n", $values) . ";";
                        $output[] = "";
                    }
                }
            }

            // Views
            $views = $pdo->query("
                SELECT viewname, definition FROM pg_views
                WHERE schemaname = '{$schema}'
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($views as $view) {
                $output[] = "DROP VIEW IF EXISTS \"{$schema}\".\"{$view['viewname']}\" CASCADE;";
                $output[] = "CREATE VIEW \"{$schema}\".\"{$view['viewname']}\" AS {$view['definition']}";
                $output[] = "";
            }

            $output[] = "SET session_replication_role = DEFAULT;";

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