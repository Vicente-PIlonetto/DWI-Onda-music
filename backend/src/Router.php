<?php
declare(strict_types=1);
namespace Onda;
final class Router
{
    private array $routes = [];
    public function add(
        string $method,
        string $pattern,
        callable $handler,
    ): void {
        $this->routes[] = [$method, $pattern, $handler];
    }
    public function dispatch(string $method, string $path): never
    {
        $allowed = [];
        foreach ($this->routes as [$verb, $pattern, $handler]) {
            if (!preg_match("~^" . $pattern . '$~D', $path, $matches)) {
                continue;
            }
            $allowed[] = $verb;
            if ($verb !== $method) {
                continue;
            }
            array_shift($matches);
            $handler(...$matches);
            exit();
        }
        if ($allowed) {
            header("Allow: " . implode(", ", $allowed));
            Http::fail("Método não permitido", 405);
        }
        Http::fail("Rota não encontrada", 404);
    }
}
