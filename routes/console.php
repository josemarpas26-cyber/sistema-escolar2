<?php


use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
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

    $timestamp = now()->format('Y-m-d_His');
    $retentionDays = (int) env('DB_BACKUP_RETENTION_DAYS', 14);

    $cleanupThreshold = now()->subDays(max($retentionDays, 1));

    foreach (File::files($backupDiskPath) as $file) {
        if ($file->getMTime() < $cleanupThreshold->timestamp) {
            File::delete($file->getPathname());
        }
    }

    $driver = $connection['driver'] ?? null;

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

    if (! in_array($driver, ['mysql', 'pgsql'], true)) {
        $this->error("Driver '{$driver}' não suportado para backup automático.");

        return self::FAILURE;
    }

    $database = $connection['database'] ?? null;
    $host = $connection['host'] ?? '127.0.0.1';
    $port = (string) ($connection['port'] ?? ($driver === 'pgsql' ? 5432 : 3306));
    $username = $connection['username'] ?? '';
    $password = (string) ($connection['password'] ?? '');

    if (! $database || ! $username) {
        $this->error('Configurações insuficientes para gerar backup da base de dados.');

        return self::FAILURE;
    }

    $targetFile = "{$backupDiskPath}/{$defaultConnection}_{$timestamp}.sql";
    
    $mysqldumpPath = env('MYSQLDUMP_PATH', 'mysqldump');

if ($driver === 'pgsql') {
    $connectionString = sprintf(
        'postgresql://%s:%s@%s:%s/%s?sslmode=require',
        $username,
        $password,
        $host,
        $port,
        $database
    );

    $command = sprintf(
        'pg_dump --no-owner --no-privileges --clean --if-exists "%s" > %s',
        $connectionString,
        escapeshellarg($targetFile),
    );
} else {
    $command = sprintf(
        '%s --host=%s --port=%s --user=%s --password=%s %s --routines --triggers --events > %s',
        $mysqldumpPath,
        escapeshellarg($host),
        escapeshellarg($port),
        escapeshellarg($username),
        escapeshellarg($password),
        escapeshellarg($database),
        escapeshellarg($targetFile),
    );
}

    $process = Process::fromShellCommandline($command);
    $process->setTimeout(300);
    $process->run();

    if (! $process->isSuccessful()) {
        File::delete($targetFile);

        $this->error('Falha ao executar comando de backup.');
        $this->line(trim($process->getErrorOutput()) ?: 'Sem detalhes de erro disponíveis.');

        return self::FAILURE;
    }

    $this->info("Backup criado em: {$targetFile}");

    return self::SUCCESS;
})->purpose('Cria um backup da base de dados em storage/app/backups/database');

Schedule::command('backup:database')
    ->dailyAt(env('DB_BACKUP_SCHEDULE', '02:00'))
    ->withoutOverlapping();
