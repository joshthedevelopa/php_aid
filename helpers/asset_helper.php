<?php

class AssetHelper {
    static public function assets(string $path = "") : string {
        return trim(Config::get("ASSETS_ROOT_DIR"), '"') . "/$path";
    } 

    static public function css(string $path = "") : string {
        return trim(Config::get("ASSETS_ROOT_DIR"), '"') . "/css/$path";
    }

    static public function js(string $path = "") : string {
        return trim(Config::get("ASSETS_ROOT_DIR"), '"') . "/js/$path";
    }
}