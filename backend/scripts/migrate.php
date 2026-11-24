<?php
declare(strict_types=1);
require __DIR__ . "/../src/bootstrap.php";
use Onda\Database as DB;
DB::query("CREATE TABLE IF NOT EXISTS migrations (name VARCHAR(190) PRIMARY KEY, applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
DB::query("SELECT GET_LOCK(?, 30)", ["onda_migrations"]);
try {
    foreach (glob(__DIR__ . "/../migrations/*.sql") as $file) {
        $name = basename($file);
        if (DB::query("SELECT name FROM migrations WHERE name=?", [$name])->fetch()) continue;
        // MySQL DDL implicitly commits, so each file is marked only after all statements succeed.
        foreach (explode(";", file_get_contents($file)) as $sql) {
            if (trim($sql) !== "") DB::query($sql);
        }
        DB::query("INSERT INTO migrations(name) VALUES (?)", [$name]);
        echo "Migrated $name\n";
    }
} finally {
    DB::query("SELECT RELEASE_LOCK(?)", ["onda_migrations"]);
}
