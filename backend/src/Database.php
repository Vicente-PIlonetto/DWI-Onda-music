<?php
declare(strict_types=1);
namespace Onda;
use PDO;
final class Database
{
    private static ?PDO $connection = null;
    public static function connection(): PDO
    {
        return self::$connection ??= new PDO(
            "mysql:host=" .
                (getenv("DB_HOST") ?: "mariadb") .
                ";dbname=" .
                getenv("MYSQL_DATABASE") .
                ";charset=utf8mb4",
            getenv("MYSQL_USER"),
            getenv("MYSQL_PASSWORD"),
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        );
    }
    public static function query(string $sql, array $params = []): \PDOStatement
    {
        $statement = self::connection()->prepare($sql);
        $statement->execute($params);
        return $statement;
    }
}
