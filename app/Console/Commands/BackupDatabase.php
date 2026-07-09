<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'lms:backup-db';

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
            $this->info('Backup SQLite dibuat: '.$dest);

            return self::SUCCESS;
        }

        if ($connection === 'mysql') {
            $dest = $dir.DIRECTORY_SEPARATOR."backup_{$stamp}.sql";
            $this->dumpMysql($dest);
            $this->info('Backup MySQL dibuat: '.$dest);

            return self::SUCCESS;
        }

        $this->error('Koneksi database tidak didukung untuk backup: '.$connection);

        return self::FAILURE;
    }

    /**
     * Dump MySQL murni-PHP (tanpa mysqldump/exec — cocok untuk shared hosting
     * yang memblokir exec). Cukup untuk basis data kecil (skala kampus).
     */
    private function dumpMysql(string $dest): void
    {
        $pdo = \Illuminate\Support\Facades\DB::connection('mysql')->getPdo();
        $handle = fopen($dest, 'w');

        fwrite($handle, "-- SIAKAD — backup ".now()->toDateTimeString()."\n");
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
    }
}
