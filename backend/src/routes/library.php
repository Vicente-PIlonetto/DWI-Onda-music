<?php
$auth = new \Onda\Controllers\AuthController();
$library = new \Onda\Controllers\LibraryController();
foreach (["login", "register", "logout"] as $action) {
    $router->add("POST", "/api/auth/" . $action, [$auth, $action]);
}
$router->add("GET", "/api/playlists", [$library, "lists"]);
$router->add("POST", "/api/playlists", [$library, "create"]);
$router->add("POST", "/api/playlists/([0-9]+)/cover", [$library, "setCover"]);
$router->add("DELETE", "/api/playlists/([0-9]+)/cover", [$library, "removeCover"]);
foreach (
    ["GET" => "detail", "PUT" => "update", "DELETE" => "delete"]
    as $method => $action
) {
    $router->add($method, "/api/playlists/([0-9]+)", [$library, $action]);
}
foreach (["POST" => true, "DELETE" => false] as $method => $add) {
    $router->add(
        $method,
        "/api/playlists/([0-9]+)/tracks",
        fn($id) => $library->tracks($id, $add),
    );
    $router->add(
        $method,
        "/api/likes/([0-9]+)",
        fn($id) => $library->like($id, $add),
    );
}
$router->add("PUT", "/api/playlists/([0-9]+)/tracks", [$library, "reorder"]);
$router->add("GET", "/api/likes", [$library, "likes"]);
