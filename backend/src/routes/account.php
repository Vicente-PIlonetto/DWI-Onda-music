<?php
$account = new \Onda\Controllers\AccountController();
$router->add("PUT", "/api/me", [$account, "update"]);
$router->add("DELETE", "/api/me", [$account, "delete"]);
$router->add("PUT", "/api/me/password", [$account, "password"]);
