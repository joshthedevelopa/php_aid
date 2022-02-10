<?php
include "config/init.php";

$router = new Router(
    [
        new ViewRoute(
            request: "/",
            view: "index.php"
        ),
        new NestedRoute(
            request: "users",
            controller: UserController::class
        ),
    ]
);

$router->parse();
exit($router->route());
