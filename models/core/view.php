<?php

class View
{
    const SUCCESS = 200;
    const PARAMETER_ERROR = 2;
    const INVALID_METHOD = 404;
    const UNKNOWN_ERROR = 4;
    const SERVER_ERROR = 500;

    public int $status;
    public string $title;
    public string $message;
    public array $data;
    public bool $isHTML = false;
    public array $headers = [];

    public function __construct(
        string $title,
        string $message,
        int $status = VIEW::SUCCESS,
        array $data = [],
        bool $isHTML = false,
        array $headers = []
    ) {
        $this->status = $status;
        $this->title = $title;
        $this->message = $message;
        $this->data = $data;
        $this->isHTML = $isHTML;
        $this->headers = $headers;
    }

    public function json(array $exclude = []): array
    {
        $arr = [
            "status" => $this->status,
            "title" => $this->title,
            "message" => $this->message,
            "data" => $this->data,
            "is_html" => $this->isHTML,
            "headers" => $this->headers
        ];

        foreach ($exclude as $value) {
           unset($arr[$value]);
        }
        
        return $arr;
    }

    static public function object(array $map = []): View
    {
        return new View(
            status: $map['status'] ?? View::SUCCESS,
            title: $map['title'] ?? null,
            message: $map['message'] ?? null,
            data: $map['data'] ?? [],
            isHTML: $map['is_html'] == true,
            headers: $map['headers'] ?? []
        );
    }
}