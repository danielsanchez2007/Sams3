<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SamsExportSqlCommand extends Command
{
    protected $signature = 'sams:export-sql {--path= : Ruta de salida del .sql}';

    protected $description = 'Exporta la base de datos a un SQL listo para phpMyAdmin';

    public function handle(): int
    {
        $default = storage_path('app/private/exports/sams-'.now()->format('Y-m-d-His').'.sql');
        $path = (string) ($this->option('path') ?: $default);
        File::ensureDirectoryExists(dirname($path));

        $driver = Schema::getConnection()->getDriverName();
        $lines = [
            '-- SAMS dump '.now()->toDateTimeString(),
            'SET NAMES utf8mb4;',
            'SET FOREIGN_KEY_CHECKS=0;',
            '',
        ];

        if ($driver !== 'mysql') {
            $this->error('sams:export-sql está pensado para MySQL (phpMyAdmin). Conexión actual: '.$driver);

            return self::FAILURE;
        }

        try {
            $tables = Schema::getTableListing();
        } catch (Throwable $e) {
            $this->error('No se pudieron leer las tablas: '.$e->getMessage());

            return self::FAILURE;
        }

        foreach ($tables as $table) {
            $lines[] = "-- Tabla {$table}";
            if ($driver === 'mysql') {
                $create = DB::select("SHOW CREATE TABLE `{$table}`");
                $sql = $create[0]->{'Create Table'} ?? null;
                if ($sql) {
                    $lines[] = "DROP TABLE IF EXISTS `{$table}`;";
                    $lines[] = $sql.';';
                    $lines[] = '';
                }
            }

            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $data = (array) $row;
                $cols = implode(', ', array_map(fn ($col) => '`'.str_replace('`', '``', $col).'`', array_keys($data)));
                $vals = implode(', ', array_map(fn ($value) => $this->sqlValue($value), array_values($data)));
                $lines[] = "INSERT INTO `{$table}` ({$cols}) VALUES ({$vals});";
            }
            $lines[] = '';
        }

        $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        File::put($path, implode("\n", $lines));
        $this->info('SQL exportado: '.$path);

        return self::SUCCESS;
    }

    private function sqlValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $string = (string) $value;

        return "'".str_replace(['\\', "'"], ['\\\\', "\\'"], $string)."'";
    }
}
