<?php


class UserController extends ViewController
{


    static public function routes(): array
    {
        return [
            new ViewRoute(
                request: "{int:id}/update",
                view: "auth"
            ),
        ];
    }

    public function auth()
    {
        return new View(
            title: "",
            message: "",
            data: $this->context,
        );
    }
}
