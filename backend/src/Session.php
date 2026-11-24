<?php
declare(strict_types=1);
namespace Onda;
final class Session
{
    /** "Lembrar de mim": 30 dias. */
    public const REMEMBER = 30 * 24 * 3600;
    public static function start(): void
    {
        ini_set("session.use_strict_mode", "1");
        // Os arquivos de sessão precisam durar tanto quanto o cookie persistente.
        ini_set("session.gc_maxlifetime", (string) self::REMEMBER);
        ini_set("session.use_only_cookies", "1");
        session_name("onda_session");
        session_set_cookie_params([
            "lifetime" => 0,
            "path" => "/",
            "secure" => getenv("SESSION_SECURE") === "1",
            "httponly" => true,
            "samesite" => "Lax",
        ]);
        session_start();
        $_SESSION["csrf"] ??= bin2hex(random_bytes(32));
        // Renova o prazo do cookie persistente uma vez por dia de uso.
        if (!empty($_SESSION["remember"]) && ($_SESSION["remember_at"] ?? 0) < time() - 86400) {
            self::persist();
        }
    }
    /** Grava o cookie com validade de 30 dias se a sessão pediu "lembrar de mim". */
    public static function persist(): void
    {
        if (empty($_SESSION["remember"])) {
            return;
        }
        $_SESSION["remember_at"] = time();
        setcookie(session_name(), session_id(), [
            "expires" => time() + self::REMEMBER,
            "path" => "/",
            "secure" => getenv("SESSION_SECURE") === "1",
            "httponly" => true,
            "samesite" => "Lax",
        ]);
    }
    public static function user(): array
    {
        if (empty($_SESSION["user_id"])) {
            Http::fail("Entre na sua conta", 401);
        }
        $user = Database::query(
            "SELECT id,name,email,role FROM users WHERE id=?",
            [$_SESSION["user_id"]],
        )->fetch();
        if (!$user) {
            Http::fail("Sessão inválida", 401);
        }
        return $user;
    }
    public static function csrf(): void
    {
        if (
            !hash_equals($_SESSION["csrf"], $_SERVER["HTTP_X_CSRF_TOKEN"] ?? "")
        ) {
            Http::fail("Token CSRF inválido", 403);
        }
    }
}
