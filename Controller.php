<?php

namespace app\core;

class Controller
{
    protected function render($view, $params)
    {
       return Application::$app->router->renderView($view, $params);
    }

    protected function redirect($rout)
    {
        $link = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]";
        header("Location: $link/$rout");
        exit();
    }
}