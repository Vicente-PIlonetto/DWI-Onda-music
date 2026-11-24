<?php
declare(strict_types=1);
namespace Onda\Repositories;

use Onda\Database as DB;

/**
 * Mensagens do formulário público de contato.
 * Regras de negócio aplicadas no servidor (independentes do JavaScript):
 *  - a mensagem precisa ter de 10 a 2000 caracteres;
 *  - cada email pode enviar no máximo LIMIT_PER_DAY mensagens em 24 horas.
 */
final class ContactRepository
{
    public const SUBJECTS = [
        'suporte' => 'Suporte com a conta',
        'sugestao' => 'Sugestão de música ou recurso',
        'artista' => 'Sou artista e quero publicar',
        'outro' => 'Outro assunto',
    ];
    public const LIMIT_PER_DAY = 3;

    /**
     * Valida e sanitiza os dados enviados.
     * @return array{0: array<string,string>, 1: array<string,string>} [dados limpos, erros por campo]
     */
    public static function validate(array $input): array
    {
        $field = fn(string $name): string => is_string($input[$name] ?? null) ? trim($input[$name]) : '';
        $data = [
            'name' => $field('name'),
            'email' => strtolower($field('email')),
            'phone' => $field('phone'),
            'subject' => $field('subject'),
            'message' => $field('message'),
        ];
        $errors = [];
        if (!preg_match("/^[\\p{L}' ]{2,160}$/u", $data['name'])) {
            $errors['name'] = 'Informe seu nome usando apenas letras (2 a 160).';
        }
        if (strlen($data['email']) > 254 || filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Informe um email válido.';
        }
        if ($data['phone'] !== '' && !preg_match('/^\(?\d{2}\)?\s?\d{4,5}-?\d{4}$/', $data['phone'])) {
            $errors['phone'] = 'Use o formato (49) 99999-9999.';
        }
        if (!array_key_exists($data['subject'], self::SUBJECTS)) {
            $errors['subject'] = 'Escolha um assunto da lista.';
        }
        $length = mb_strlen($data['message']);
        if ($length < 10 || $length > 2000) {
            $errors['message'] = 'A mensagem deve ter de 10 a 2000 caracteres.';
        }
        if (($input['consent'] ?? '') !== '1') {
            $errors['consent'] = 'Confirme que os dados enviados são fictícios.';
        }
        if (!$errors && self::recentCount($data['email']) >= self::LIMIT_PER_DAY) {
            $errors['email'] = 'Este email já enviou ' . self::LIMIT_PER_DAY . ' mensagens nas últimas 24 horas. Tente amanhã.';
        }
        return [$data, $errors];
    }

    public static function recentCount(string $email): int
    {
        return (int) DB::query(
            'SELECT count(*) FROM contact_messages WHERE email=? AND created_at > NOW() - INTERVAL 1 DAY',
            [$email],
        )->fetchColumn();
    }

    public static function create(array $data): void
    {
        DB::query(
            'INSERT INTO contact_messages(name,email,phone,subject,message) VALUES (?,?,?,?,?)',
            [$data['name'], $data['email'], $data['phone'] !== '' ? $data['phone'] : null, $data['subject'], $data['message']],
        );
    }

    /** Lista paginada por cursor (id decrescente: mais recentes primeiro). */
    public static function list(?int $before, bool $unreadOnly, int $limit = 20): array
    {
        $where = [];
        $params = [];
        if ($before) { $where[] = 'id < ?'; $params[] = $before; }
        if ($unreadOnly) { $where[] = 'read_at IS NULL'; }
        $sql = 'SELECT id,name,email,phone,subject,message,read_at,created_at FROM contact_messages'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '')
            . ' ORDER BY id DESC LIMIT ' . ($limit + 1);
        return DB::query($sql, $params)->fetchAll();
    }

    public static function unreadCount(): int
    {
        return (int) DB::query('SELECT count(*) FROM contact_messages WHERE read_at IS NULL')->fetchColumn();
    }

    public static function toggleRead(int $id): void
    {
        $statement = DB::query('UPDATE contact_messages SET read_at = IF(read_at IS NULL, NOW(), NULL) WHERE id=?', [$id]);
        if ($statement->rowCount() === 0) throw new \RuntimeException('Mensagem não encontrada.');
    }

    public static function delete(int $id): void
    {
        $statement = DB::query('DELETE FROM contact_messages WHERE id=?', [$id]);
        if ($statement->rowCount() === 0) throw new \RuntimeException('Mensagem não encontrada.');
    }
}
