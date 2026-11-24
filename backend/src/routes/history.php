<?php
$history = new \Onda\Controllers\HistoryController();
$router->add("GET", "/api/history", [$history, "recent"]);
$router->add("POST", "/api/history", [$history, "record"]);
$router->add("DELETE", "/api/history", [$history, "clear"]);
