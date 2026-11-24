<?php
$router->add("GET", "/api/search", [
    new \Onda\Controllers\SearchController(),
    "search",
]);
