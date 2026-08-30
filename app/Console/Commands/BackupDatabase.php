<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'lms:backup-db {--without-siakad : Jangan backup database SIAKAD terintegrasi}';

    protected $description = 'Backup database (SQLite/MySQL) ke storage/app/backups';

    public function handle(): int
    {
        $connection = config('database.default');
        $stamp = now()->format('Y-m-d_His');
        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        if ($connection === 'sqlite') {
            $src = config('database.connections.sqlite.database');
            if (! is_file($src)) {
                $this->error('Berkas SQLite tidak ditemukan: '.$src);

                return self::FAILURE;
            }
            $dest = $dir.DIRECTORY_SEPARATOR."backup_{$stamp}.sqlite";
            copy($src, $dest);
            @chmod($dest, 0600);
            $this->info('Backup SQLite dibuat: '.$dest);
        } elseif ($connection === 'mysql') {
            $dest = $dir.DIRECTORY_SEPARATOR."lms_{$stamp}.sql";
            $this->dumpMysql($dest, 'mysql');
            $this->info('Backup MySQL LMS dibuat: '.$dest);
        } else {
            $this->error('Koneksi database tidak didukung untuk backup: '.$connection);

            return self::FAILURE;
        }

        if (! $this->option('without-siakad') && $this->siakadConfigured()) {
            $siakadDest = $dir.DIRECTORY_SEPARATOR."siakad_{$stamp}.sql";
            $this->dumpMysql($siakadDest, 'siakad');
            $this->info('Backup MySQL SIAKAD dibuat: '.$siakadDest);
        }

        $this->copyOffsite($dir);
        $this->prune($dir);

        return self::SUCCESS;
    }

    /**
     * Dump MySQL murni-PHP (tanpa mysqldump/exec — cocok untuk shared hosting
     * yang memblokir exec). Cukup untuk basis data kecil (skala kampus).
     */
    private function dumpMysql(string $dest, string $connection): void
    {
        $pdo = DB::connection($connection)->getPdo();
        $handle = fopen($dest, 'w');
        if ($handle === false) {
            throw new \RuntimeException('Berkas backup tidak dapat dibuat.');
        }

        fwrite($handle, "-- {$connection} backup ".now()->toDateTimeString()."\n");
        fwrite($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n");

        $tables = $pdo->query('SHOW TABLES')->fetchAll(\PDO::FETCH_COLUMN);
        foreach ($tables as $table) {
            $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(\PDO::FETCH_ASSOC);
            $ddl = $create['Create Table'] ?? $create['Create View'] ?? null;
            if (! $ddl) {
                continue;
            }

            fwrite($handle, "\nDROP TABLE IF EXISTS `$table`;\n".$ddl.";\n");

            $rows = $pdo->query("SELECT * FROM `$table`");
            while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                $cols = implode(', ', array_map(fn ($c) => "`$c`", array_keys($row)));
                $vals = implode(', ', array_map(
                    fn ($v) => is_null($v) ? 'NULL' : $pdo->quote((string) $v),
                    array_values($row),
                ));
                fwrite($handle, "INSERT INTO `$table` ($cols) VALUES ($vals);\n");
            }
        }

        fwrite($handle, "\nSET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);
        @chmod($dest, 0600);
    }

    private function siakadConfigured(): bool
    {
        $config = (array) config('database.connections.siakad', []);

        return (bool) config('backup.siakad_enabled')
            && ($config['driver'] ?? null) === 'mysql'
            && filled($config['database'] ?? null)
            && filled($config['username'] ?? null);
    }

    private function prune(string $dir): void
    {
        $cutoff = now()->subDays(max(1, (int) config('backup.retention_days', 30)))->timestamp;
        foreach (File::files($dir) as $file) {
            if ($file->getMTime() < $cutoff && in_array($file->getExtension(), ['sql', 'sqlite'], true)) {
                File::delete($file->getPathname());
            }
        }
    }

    private function copyOffsite(string $dir): void
    {
        $target = config('backup.copy_path');
        if (! filled($target)) {
            return;
        }
        File::ensureDirectoryExists($target);
        $cutoff = now()->subDays(max(1, (int) config('backup.retention_days', 30)))->timestamp;
        foreach (File::files($target) as $oldFile) {
            if ($oldFile->getMTime() < $cutoff && in_array($oldFile->getExtension(), ['sql', 'sqlite', 'enc'], true)) {
                File::delete($oldFile->getPathname());
            }
        }
        $encryptionKey = (string) config('backup.encryption_key');
        foreach (File::files($dir) as $file) {
            if ($file->getMTime() >= now()->subMinutes(5)->timestamp) {
                $destination = rtrim($target, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$file->getFilename();
                if ($encryptionKey !== '') {
                    $plain = File::get($file->getPathname());
                    $iv = random_bytes(12);
                    $tag = '';
                    $cipher = openssl_encrypt($plain, 'aes-256-gcm', hash('sha256', $encryptionKey, true), OPENSSL_RAW_DATA, $iv, $tag);
                    if ($cipher === false) {
                        throw new \RuntimeException('Enkripsi salinan backup gagal.');
                    }
                    File::put($destination.'.enc', 'LMSBK1'.$iv.$tag.$cipher);
                    @chmod($destination.'.enc', 0600);
                } else {
                    File::copy($file->getPathname(), $destination);
                    @chmod($destination, 0600);
                }
            }
        }
    }
}
