<?php

class Route
{
    public string $request;
    public string $method;


    public function __construct(
        string $request,
        string $method = "GET"
    ) {
        $this->request = $request;
        $this->method = $method;
    }
}

class ViewRoute extends Route
{
    public string $view;

    public function __construct(
        string $request,
        string $view,
        string $method = "GET"
    ) {
        parent::__construct($request, $method);
        $this->view = $view;
    }
}

class NestedRoute extends Route
{
    public string $controller;

    public function __construct(
        string $request,
        string $controller,
        string $method = "GET"
    ) {
        parent::__construct($request, $method);
        $this->controller = $controller;
    }
}

class Router
{
    static private View $view;
    static private array $_routes = [];

    public function __construct(array $routes)
    {
        self::$_routes = $routes;
    }

    static public function _parser(
        string &$route,
        string $request,
    ): array {
        $results = [];

        $route = trim($route, "/");
        $route = str_replace("/", "\/", $route);
        if (preg_match(
            "/{[a-z]+:[a-z]+}/",
            $route,
            $matches
        )) {
            foreach ($matches as $key => $value) {
                $arr_text = str_split($value);
                array_pop($arr_text);
                array_shift($arr_text);
                $_text =  implode("", $arr_text);

                list($type, $name) = explode(":", $_text);

                switch ($type) {
                    case 'int':
                        $_url_pattern = "/(?'$name'[0-9]+)/";
                        break;

                    default:
                        $_url_pattern = "/(?'$name'[a-zA-Z0-9]+)/";
                        break;
                }

                $route = str_replace($value, trim($_url_pattern, "/"), $route);
                if (preg_match($_url_pattern, $request, $url_data)) {
                    $results[$name] = $url_data[$name];
                }
            }
        }

        return $results;
    }

    public function parse(): View
    {
        $this->context = [];
        $this->request = $_POST;
        $this->files = $_FILES;
        $this->method = $_SERVER['REQUEST_METHOD'];

        $root_dir = trim(Config::get("ROOT_DIR"), "/");
        $pattern = str_replace(
            '"',
            "",
            '/' . $root_dir . '/'
        );
        $url = trim(preg_replace(
            $pattern,
            "",
            trim($_SERVER['REQUEST_URI'], "/")
        ), "/");

        $list = explode("?", $url);
        $url = $list[0];

        if (preg_match(
            "/^" . trim(Config::get("ASSETS_ROOT_DIR"), '"') . "\/.*\..*$/",
            $url
        )) {
            if (file_exists($url)) {
                return self::$view = new View(
                    title: "",
                    message: "../$url",
                    headers: [
                        "Accept-Ranges" => "bytes",
                        "Content-Type" => getallheaders()["Accept"]
                    ],
                    isHTML: true
                );
            }
        }

        if (count($list) > 1) {
            $query = $list[1];
            $arr_query = explode("&", $query);

            foreach ($arr_query as $value) {
                $list = explode("=", $value);

                if (count($list) > 1) {
                    list($key, $value) = $list;
                    $this->context[$key] = $value;
                }
            }
        }

        foreach (self::$_routes as $key => $route) {
            $route->request = trim($route->request, "/");
            $route->request = str_replace("/", "\/", $route->request);

            if (is_a($route, "NestedRoute")) {
                if (preg_match("/^" . $route->request . "/", $url)) {
                    $url = ltrim($url, $route->request . "/");

                    foreach ($route->controller::routes() as $key => $sub_route) {
                        $this->context = array_merge(
                            $this->context,
                            self::_parser($route->request, $url)
                        );

                        if (preg_match("/^" . $sub_route->request . "$/", $url)) {
                            $controller = new $route->controller(
                                files: $this->files,
                                data: $this->request,
                                context: $this->context
                            );
                            return self::$view = $controller->{$sub_route->view}();
                        }
                    }
                }
            } else {
                if ($this->method === $route->method) {
                    $this->context = array_merge(
                        $this->context,
                        self::_parser($route->request, $url)
                    );

                    if (preg_match("/^" . $route->request . "$/", $url)) {
                        if (preg_match("/^[a-zA-Z]+@[a-zA-Z]+$/", $route->view)) {
                            list($controller, $action) = explode("@", $route->view);

                            $controller = new $controller(
                                files: $this->files,
                                data: $this->request,
                                context: $this->context
                            );
                            return self::$view = $controller->{$action}();
                        } else {
                            return self::$view = new View(
                                title: "",
                                message: $route->view,
                                isHTML: true
                            );
                        }
                    }
                }
            }
        }

        return self::$view = new View(
            status: View::INVALID_METHOD,
            title: "404 ERROR",
            message: "This endpoint does not exist",
        );
    }

    public function route()
    {
        foreach (self::$view->headers as $key => $value) {
            header("$key: $value");
        }

        if (self::$view->isHTML) {
            $context = $this->context;
            $get = $_GET;

            return include_once trim(Config::get("VIEW_ROOT_DIR"), '"') . "/" . self::$view->message;
        } else {
            http_response_code(self::$view->status);
            return json_encode(self::$view->json(
                exclude: ['is_html', "data", "status", "headers"]
            ));
        }
    }
}
