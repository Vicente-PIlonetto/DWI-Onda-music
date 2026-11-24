<?php
declare(strict_types=1);
namespace Onda\Controllers;
use Onda\{Database as DB, Http, Session};
// Conta do próprio usuário: nome de exibição, troca de senha e exclusão.
final class AccountController
{
    public function update(): never
    {
        $user = Session::user();
        Session::csrf();
        $name = Http::text(Http::body(), "name", 160);
        // Mesma regra do cadastro.
        if (!preg_match("/^[\\p{L}' ]{2,160}$/u", $name)) {
            Http::fail("Use apenas letras e espaços no nome", 422);
        }
        DB::query("UPDATE users SET name=? WHERE id=?", [$name, $user["id"]]);
        Http::json(["user" => Session::user()]);
    }
    public function password(): never
    {
        $user = Session::user();
        Session::csrf();
        $body = Http::body();
        $this->verify((int) $user["id"], Http::text($body, "current_password", 1024));
        $new = Http::text($body, "new_password", 1024);
        if (strlen($new) < 12) {
            Http::fail("Use uma senha de pelo menos 12 caracteres", 422);
        }
        DB::query("UPDATE users SET password_hash=? WHERE id=?", [
            password_hash($new, PASSWORD_ARGON2ID),
            $user["id"],
        ]);
        // Novo id de sessão e novo token: o cookie antigo deixa de valer.
        session_regenerate_id(true);
        $_SESSION["csrf"] = bin2hex(random_bytes(32));
        Session::persist();
        Http::json(["csrf_token" => $_SESSION["csrf"]]);
    }
    public function delete(): never
    {
        $user = Session::user();
        Session::csrf();
        $this->verify((int) $user["id"], Http::text(Http::body(), "password", 1024));
        if ($user["role"] === "admin" &&
            (int) DB::query("SELECT COUNT(*) FROM users WHERE role='admin'")->fetchColumn() <= 1) {
            Http::fail("O último administrador não pode excluir a própria conta", 409);
        }
        $covers = DB::query("SELECT id,cover_path FROM playlists WHERE user_id=? AND cover_path IS NOT NULL", [$user["id"]])->fetchAll();
        DB::query("DELETE FROM users WHERE id=?", [$user["id"]]);
        $root = dirname(__DIR__, 3) . "/storage/covers/";
        foreach ($covers as $p) {
            if (preg_match('/^playlist-' . $p["id"] . '-[a-f0-9]{24}\\.(?:jpg|png|webp)$/D', $p["cover_path"])) {
                @unlink($root . $p["cover_path"]);
            }
        }
        $_SESSION = [];
        session_destroy();
        http_response_code(204);
        exit();
    }
    private function verify(int $id, string $password): void
    {
        $hash = DB::query("SELECT password_hash FROM users WHERE id=?", [$id])->fetchColumn();
        if (!$hash || !password_verify($password, $hash)) {
            Http::fail("Senha atual incorreta", 403);
        }
    }
}
