<?php
declare(strict_types=1);
namespace Onda;
final class Admin {
    public static function current(): ?array {
        if (empty($_SESSION['user_id'])) return null;
        $row = Database::query('SELECT id,name,email,role FROM users WHERE id=?', [$_SESSION['user_id']])->fetch();
        return $row ?: null;
    }
    public static function guard(): array {
        $user = self::current();
        if (!$user) { header('Location: /admin/login.php'); exit; }
        if ($user['role'] !== 'admin') {
            http_response_code(403);
            echo 'Acesso permitido somente ao administrador.';
            exit;
        }
        return $user;
    }
    public static function csrf(): void {
        if (!is_string($_POST['csrf'] ?? null) || !hash_equals($_SESSION['csrf'], $_POST['csrf'])) {
            http_response_code(403);
            throw new \RuntimeException('Sessão do formulário inválida. Recarregue a página.');
        }
    }
    public static function text(string $name, int $max=160): string {
        $value = $_POST[$name] ?? '';
        if (!is_string($value) || trim($value)==='' || strlen($value)>$max) throw new \RuntimeException('Confira o campo '.$name.'.');
        return trim($value);
    }
    public static function id(mixed $value): int {
        $id=filter_var($value,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if ($id===false) throw new \RuntimeException('Identificador inválido.');
        return $id;
    }
    public static function login(string $email,string $password): void {
        Database::query('DELETE FROM login_attempts WHERE expires_at<now()');
        foreach (['ip:'.($_SERVER['REMOTE_ADDR']??''),'email:'.$email] as $key) {
            $bucket=hash('sha256',$key);
            Database::query("INSERT INTO login_attempts(bucket,attempts,expires_at) VALUES (?,1,DATE_ADD(NOW(), INTERVAL 15 MINUTE)) ON DUPLICATE KEY UPDATE attempts=attempts+1",[$bucket]);
            $count=Database::query('SELECT attempts FROM login_attempts WHERE bucket=?',[$bucket])->fetchColumn();
            if ($count>10) { http_response_code(429); throw new \RuntimeException('Muitas tentativas. Aguarde 15 minutos.'); }
        }
        $user=Database::query('SELECT id,password_hash,role FROM users WHERE email=?',[$email])->fetch();
        if (!$user || !password_verify($password,$user['password_hash']) || $user['role']!=='admin') throw new \RuntimeException('Email ou senha de administrador inválidos.');
        session_regenerate_id(true);
        $_SESSION['user_id']=$user['id'];
        $_SESSION['csrf']=bin2hex(random_bytes(32));
    }
    public static function h(mixed $value): string { return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
}
