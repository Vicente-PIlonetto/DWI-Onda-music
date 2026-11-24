<?php
declare(strict_types=1);
require __DIR__ . "/../src/bootstrap.php";
use Onda\{Router, Http, Session};
use Onda\Controllers\CatalogController;
header("Cache-Control: no-store");
try {
    $path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
    // O healthcheck roda a cada poucos segundos; não precisa criar sessão.
    if ($path !== "/api/health") {
        Session::start();
    }
    $router = new Router();
    $catalog = new CatalogController();
    foreach (["tracks", "albums", "artists"] as $type) {
        $router->add("GET", "/api/" . $type, fn() => $catalog->listing($type));
        $router->add(
            "GET",
            "/api/" . $type . "/([0-9]+)",
            fn($id) => $catalog->detail($type, $id),
        );
    }
    $router->add("GET", "/api/stream/([0-9]+)", [$catalog, "stream"]);
    $router->add(
        "GET",
        "/api/auth/me",
        fn() => Http::json([
            "user" => isset($_SESSION["user_id"]) ? Session::user() : null,
            "csrf_token" => $_SESSION["csrf"],
        ]),
    );
    // Additional feature routes are kept separate from the front controller.
    foreach (glob(__DIR__ . "/../src/routes/*.php") as $routes) {
        require $routes;
    }
    $router->dispatch(
        $_SERVER["REQUEST_METHOD"],
        $path,
    );
} catch (Throwable $e) {
    error_log((string) $e);
    Http::fail("Erro interno do servidor", 500);
}
