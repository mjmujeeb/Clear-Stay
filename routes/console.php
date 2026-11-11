<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('db:test-connection {connection?}', function ($connection = null) {
    $conn = $connection ?: config('database.default');
    $this->info("Testing connection to database connection [{$conn}]...");

    try {
        $pdo = DB::connection($conn)->getPdo();
        $result = DB::connection($conn)->select('SELECT 1 as ping');

        $databaseName = DB::connection($conn)->getDatabaseName();
        $driver = DB::connection($conn)->getDriverName();
        $host = config("database.connections.{$conn}.host") ?: 'N/A (URL/Socket)';

        $this->info("Connection successful!");
        $this->line("  Driver:   {$driver}");
        $this->line("  Database: {$databaseName}");
        $this->line("  Host:     {$host}");

        if ($driver === 'pgsql') {
            $version = DB::connection($conn)->select('SELECT version()');
            if (!empty($version) && isset($version[0]->version)) {
                $this->line("  Version:  " . strtok($version[0]->version, "\n"));
            }
        }
    } catch (\Throwable $e) {
        // Redact any credentials or passwords embedded in PDO exception messages
        $safeMessage = preg_replace('/:[^:@\s]+@/', ':****@', $e->getMessage());
        $safeMessage = preg_replace('/password=[^\s;]+/', 'password=****', $safeMessage);

        $this->error("Connection failed: {$safeMessage}");
        return 1;
    }

    return 0;
})->purpose('Safely test connectivity to a configured database connection');
