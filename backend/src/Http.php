<?php
declare(strict_types=1);
namespace Onda;
final class Http
{
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        exit();
    }
    public static function fail(string $message, int $status = 400): never
    {
        self::json(["error" => $message], $status);
    }
    public static function body(): array
    {
        if (
            !str_starts_with($_SERVER["CONTENT_TYPE"] ?? "", "application/json")
        ) {
            self::fail("Envie application/json", 415);
        }
        try {
            $body = json_decode(
                file_get_contents("php://input"),
                true,
                32,
                JSON_THROW_ON_ERROR,
            );
        } catch (\JsonException) {
            self::fail("JSON inválido");
        }
        if (!is_array($body) || (array_is_list($body) && $body !== [])) {
            self::fail("Objeto JSON esperado");
        }
        return $body;
    }
    public static function text(
        array $body,
        string $field,
        int $max = 160,
    ): string {
        $v = $body[$field] ?? null;
        if (!is_string($v) || trim($v) === "" || strlen($v) > $max) {
            self::fail("Campo inválido: " . $field, 422);
        }
        return trim($v);
    }
    public static function positive(mixed $v): int
    {
        if (!is_scalar($v) || !preg_match('/^[1-9][0-9]{0,9}$/', (string) $v)) {
            self::fail("Identificador inválido", 422);
        }
        return (int) $v;
    }
    public static function page(): array
    {
        $cursor = $_GET["cursor"] ?? "0";
        $limit = $_GET["limit"] ?? "20";
        if (
            !is_string($cursor) ||
            !ctype_digit($cursor) ||
            strlen($cursor) > 15
        ) {
            self::fail("Cursor inválido", 422);
        }
        if (
            !is_string($limit) ||
            !ctype_digit($limit) ||
            (int) $limit < 1 ||
            (int) $limit > 100
        ) {
            self::fail("Limite deve ser de 1 a 100", 422);
        }
        return [(int) $cursor, (int) $limit];
    }
    public static function pageResult(
        array $rows,
        int $limit,
        string $key = "id",
    ): array {
        $more = count($rows) > $limit;
        $rows = array_slice($rows, 0, $limit);
        return [
            "items" => $rows,
            "next_cursor" => $more ? (string) end($rows)[$key] : null,
        ];
    }
}
