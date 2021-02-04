<?php

namespace app\core;

class Controller
{
    protected function render($view, $params)
    {
       return Application::$app->router->renderView($view, $params);
    }
}