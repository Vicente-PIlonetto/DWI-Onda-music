<?php
declare(strict_types=1);
namespace Onda\Controllers;
use Onda\{Database as DB, Http, Session};
final class AuthController
{
    public function login(): never
    {
        Session::csrf();
        $body = Http::body();
        $email = strtolower(Http::text($body, "email", 254));
        $password = Http::text($body, "password", 1024);
        // Atomic counters survive cookie resets and concurrent requests. Do not trust X-Forwarded-For.
        DB::query("DELETE FROM login_attempts WHERE expires_at<now()");
        foreach (
            ["ip:" . ($_SERVER["REMOTE_ADDR"] ?? ""), "email:" . $email]
            as $key
        ) {
            $bucket = hash("sha256", $key);
            DB::query(
                "INSERT INTO login_attempts(bucket,attempts,expires_at) VALUES (?,1,DATE_ADD(NOW(), INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE attempts=attempts+1",
                [$bucket],
            );
            $n = DB::query("SELECT attempts FROM login_attempts WHERE bucket=?", [$bucket])->fetchColumn();
            if ($n > 10) {
                header("Retry-After: 900");
                Http::fail("Muitas tentativas. Aguarde 15 minutos.", 429);
            }
        }
        $user = DB::query("SELECT * FROM users WHERE email=?", [
            $email,
        ])->fetch();
        if (!$user || !password_verify($password, $user["password_hash"])) {
            Http::fail("Email ou senha inválidos", 401);
        }
        session_regenerate_id(true);
        $_SESSION["user_id"] = $user["id"];
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
        $_SESSION["remember"] = in_array($body["remember"] ?? false, [true, "1", "on"], true);
        Session::persist();
        Http::json([
            "user" => Session::user(),
            "csrf_token" => $_SESSION["csrf"],
        ]);
    }
    public function register(): never
    {
        Session::csrf();
        $body = Http::body();
        $name = Http::text($body, "name");
        $email = strtolower(Http::text($body, "email", 254));
        $password = Http::text($body, "password", 1024);
        // Mesma regra do atributo pattern do formulário, validada de novo no servidor.
        if (!preg_match("/^[\\p{L}' ]{2,160}$/u", $name)) {
            Http::fail("Use apenas letras e espaços no nome", 422);
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Http::fail("Email inválido", 422);
        }
        if (strlen($password) < 12) {
            Http::fail("Use uma senha de pelo menos 12 caracteres", 422);
        }
        try {
            DB::query("INSERT INTO users(name,email,password_hash) VALUES (?,?,?)", [$name, $email, password_hash($password, PASSWORD_ARGON2ID)]);
        } catch (\PDOException $error) {
            if ($error->getCode() === "23000") Http::fail("Email já cadastrado", 409);
            throw $error;
        }
        $id = DB::connection()->lastInsertId();
        session_regenerate_id(true);
        $_SESSION["user_id"] = $id;
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
        Http::json(
            ["user" => Session::user(), "csrf_token" => $_SESSION["csrf"]],
            201,
        );
    }
    public function logout(): never
    {
        Session::csrf();
        $_SESSION = [];
        session_destroy();
        setcookie(session_name(), "", [
            "expires" => time() - 3600,
            "path" => "/",
            "secure" => getenv("SESSION_SECURE") === "1",
            "httponly" => true,
            "samesite" => "Lax",
        ]);
        http_response_code(204);
        exit();
    }
}
