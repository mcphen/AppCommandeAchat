<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;
use ZipArchive;

class EmailDatabaseBackup extends Command
{
    protected $signature = 'database:backup-email {--to= : Adresse e-mail du destinataire}';
    protected $description = 'Sauvegarde la base de données et envoie le fichier par e-mail';

    public function handle(): int
    {
        $recipient = (string) ($this->option('to') ?: config('backup.recipient'));
        if (! filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->error("L'adresse e-mail du destinataire est invalide.");
            return self::FAILURE;
        }

        $directory = (string) config('backup.directory');
        File::ensureDirectoryExists($directory, 0700, true);
        $this->rotateArchives($directory);
        $connectionName = DB::getDefaultConnection();
        $connection = config("database.connections.{$connectionName}");
        $driver = $connection['driver'] ?? null;
        $databaseExtension = $driver === 'sqlite' ? 'sqlite' : 'sql';
        $databaseName = pathinfo((string) ($connection['database'] ?? 'database'), PATHINFO_FILENAME);
        $safeDatabaseName = preg_replace('/[^a-zA-Z0-9_-]+/', '-', $databaseName) ?: 'database';
        $timestamp = now()->format('Y-m-d_H-i-s');
        $databaseFilename = sprintf('%s-%s.%s', $safeDatabaseName, $timestamp, $databaseExtension);
        $databasePath = $directory.DIRECTORY_SEPARATOR.$databaseFilename.'.tmp';
        $archiveFilename = sprintf('sauvegarde-complete-%s.zip', $timestamp);
        $archivePath = $directory.DIRECTORY_SEPARATOR.$archiveFilename;
        $keepArchive = false;

        try {
            $this->createBackup($driver, $connection, $databasePath);
            $this->createArchive($archivePath, $databasePath, $databaseFilename, $directory);
            $maxAttachmentBytes = max(1, (int) config('backup.attachment_max_mb')) * 1024 * 1024;
            $attachArchive = File::size($archivePath) <= $maxAttachmentBytes;
            $downloadUrl = null;

            if (! $attachArchive) {
                $keepArchive = true;
                $downloadUrl = URL::temporarySignedRoute(
                    'database-backups.download',
                    now()->addHours((int) config('backup.link_expiration_hours')),
                    ['filename' => $archiveFilename]
                );
            }

            $body = 'La sauvegarde automatique de la base de données et des fichiers de storage/app a été effectuée le '.now()->format('d/m/Y à H:i').'.';
            $body .= $attachArchive
                ? "\n\nL'archive est jointe à ce message."
                : "\n\nL'archive étant volumineuse, téléchargez-la avec ce lien temporaire :\n{$downloadUrl}";

            Mail::raw($body, function ($message) use ($recipient, $archivePath, $archiveFilename, $attachArchive): void {
                $message->to($recipient)
                    ->subject('Sauvegarde quotidienne complète - '.now()->format('d/m/Y'));
                if ($attachArchive) {
                    $message->attach($archivePath, ['as' => $archiveFilename, 'mime' => 'application/zip']);
                }
            });
            $this->info("Sauvegarde envoyée avec succès à {$recipient}.");
            return self::SUCCESS;
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Échec de la sauvegarde : '.$exception->getMessage());
            return self::FAILURE;
        } finally {
            if (File::exists($databasePath)) {
                File::delete($databasePath);
            }
            if (! $keepArchive && File::exists($archivePath)) {
                File::delete($archivePath);
            }
        }
    }

    private function createArchive(string $archivePath, string $databasePath, string $databaseFilename, string $backupDirectory): void
    {
        $zip = new ZipArchive;
        if ($zip->open($archivePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException("Impossible de créer l'archive ZIP.");
        }

        try {
            $zip->addFile($databasePath, 'database/'.$databaseFilename);
            $storagePath = storage_path('app');
            $backupRealPath = realpath($backupDirectory);
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($storagePath, RecursiveDirectoryIterator::SKIP_DOTS));
            foreach ($files as $file) {
                if (! $file->isFile()) {
                    continue;
                }
                $realPath = $file->getRealPath();
                if ($backupRealPath !== false && str_starts_with($realPath, $backupRealPath.DIRECTORY_SEPARATOR)) {
                    continue;
                }
                $relativePath = str_replace('\\', '/', substr($realPath, strlen($storagePath) + 1));
                $zip->addFile($realPath, 'storage/app/'.$relativePath);
            }
        } finally {
            $zip->close();
        }

        if (! File::exists($archivePath) || File::size($archivePath) === 0) {
            throw new RuntimeException("L'archive ZIP n'a pas été créée ou est vide.");
        }
    }

    private function rotateArchives(string $directory): void
    {
        $archives = File::glob($directory.DIRECTORY_SEPARATOR.'*.zip');

        if ($archives === []) {
            return;
        }

        $rotationThreshold = now()
            ->subHours(max(1, (int) config('backup.rotation_hours')))
            ->getTimestamp();
        $oldestArchiveTimestamp = min(array_map(
            fn (string $archive): int => File::lastModified($archive),
            $archives
        ));

        if ($oldestArchiveTimestamp <= $rotationThreshold) {
            File::delete($archives);
        }
    }

    private function createBackup(?string $driver, array $connection, string $backupPath): void
    {
        match ($driver) {
            'sqlite' => $this->backupSqlite($connection, $backupPath),
            'mysql', 'mariadb' => $this->backupMysql($connection, $backupPath),
            'pgsql' => $this->backupPostgresql($connection, $backupPath),
            default => throw new RuntimeException("Le pilote de base de données [{$driver}] n'est pas pris en charge."),
        };
        if (! File::exists($backupPath) || File::size($backupPath) === 0) {
            throw new RuntimeException("Le fichier de sauvegarde n'a pas été créé ou est vide.");
        }
    }

    private function backupSqlite(array $connection, string $backupPath): void
    {
        $source = (string) ($connection['database'] ?? '');
        if (! File::exists($source)) {
            throw new RuntimeException("Le fichier SQLite est introuvable : {$source}");
        }
        $quotedPath = str_replace("'", "''", $backupPath);
        DB::connection()->statement("VACUUM INTO '{$quotedPath}'");
    }

    private function backupMysql(array $connection, string $backupPath): void
    {
        $command = [
            (string) config('backup.binaries.mysqldump'),
            '--single-transaction', '--quick', '--routines', '--triggers',
            '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? 3306),
            '--user='.(string) ($connection['username'] ?? ''),
            '--result-file='.$backupPath,
            (string) ($connection['database'] ?? ''),
        ];
        $this->runProcess($command, ['MYSQL_PWD' => (string) ($connection['password'] ?? '')]);
    }

    private function backupPostgresql(array $connection, string $backupPath): void
    {
        $command = [
            (string) config('backup.binaries.pg_dump'),
            '--host='.(string) ($connection['host'] ?? '127.0.0.1'),
            '--port='.(string) ($connection['port'] ?? 5432),
            '--username='.(string) ($connection['username'] ?? ''),
            '--file='.$backupPath,
            (string) ($connection['database'] ?? ''),
        ];
        $this->runProcess($command, ['PGPASSWORD' => (string) ($connection['password'] ?? '')]);
    }

    private function runProcess(array $command, array $environment): void
    {
        (new Process($command, base_path(), $environment, null, 600))->mustRun();
    }
}
