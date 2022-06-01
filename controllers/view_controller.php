<?php


class ViewController {
    public array $files;
    public array $data;
    public array $context;

    static public function routes() : array {
        return [];
    }

    public function __construct(
        array $files = [],
        array $data = [],
        array $context = null,
    ) {
        $this->files = $files;
        $this->data = $data;
        $this->context = $context;
    }

}