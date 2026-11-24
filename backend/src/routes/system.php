<?php
// Estado do servidor para healthcheck do Docker e monitoramento externo.
$router->add("GET", "/api/health", function () {
    try {
        $tracks = (int) \Onda\Database::query("SELECT COUNT(*) FROM tracks WHERE status='ready'")->fetchColumn();
    } catch (Throwable $e) {
        error_log((string) $e);
        \Onda\Http::json(["status" => "error", "database" => false], 503);
    }
    $audio = is_dir(dirname(__DIR__, 3) . "/storage/audio");
    \Onda\Http::json(["status" => $audio ? "ok" : "degraded", "database" => true, "audio_storage" => $audio, "tracks" => $tracks], $audio ? 200 : 503);
});
// Faixas sorteadas para o "Tocar aleatório".
$router->add("GET", "/api/tracks/random", function () {
    $limit = $_GET["limit"] ?? "50";
    if (!is_string($limit) || !ctype_digit($limit) || (int) $limit < 1 || (int) $limit > 100) {
        \Onda\Http::fail("Limite deve ser de 1 a 100", 422);
    }
    \Onda\Http::json(["items" => \Onda\Database::query(
        \Onda\Repositories\CatalogRepository::TRACKS . " WHERE t.status='ready' ORDER BY RAND() LIMIT ?",
        [(int) $limit],
    )->fetchAll()]);
});
