<?php
require_once __DIR__ . '/../models/core/autoload.php';

spl_autoload_register(function($name) {
    if(preg_match(
        pattern: "/^[a-zA-Z]+(?'type'(Controller)|(Component)|(Helper))$/", 
        subject: $name, 
        matches: $matches
    )) {
        $class_type = strtolower($matches["type"]);
        $class_name = strtolower(str_replace($class_type, "", strtolower($name)));

        @require_once "{$class_type}s/{$class_name}_{$class_type}.php";
    } else {
        $name = strtolower($name);
        @require_once "models/{$name}.php";
    }
});

