<?php

namespace app\core;

use app\core\Services\YamlParser;
use InvalidArgumentException;

class Router
{
    protected array $routes = [];

    public Request $request;
    public Response $response;

    /**
     * Router constructor.
     * @param Request $request
     * @param Response $response
     */
    public function __construct(Request $request, Response $response)
    {
        $this->request = $request;
        $this->response = $response;
    }

    /**
     * Declares routs with GET method and sets callback for it
     * @param $path
     * @param $callback
     */
    public function get($path, $callback)
    {
        $this->routes['get'][$path] = $callback;
    }

    /**
     * Declares routs with POST method and sets callback for it
     * @param $path
     * @param $callback
     */
    public function post($path, $callback)
    {
        $this->routes['post'][$path] = $callback;
    }

    /**
     * Declares routs for any method and sets callback for it
     * @param $path
     * @param $callback
     */
    public function any($path, $callback)
    {
        $this->routes['any'][$path] = $callback;
    }

    /**
     * Loads routes from the routes.yaml file
     */
    public function loadRoutes()
    {
        $routes = YamlParser::parse(Application::$ROOT_DIR.'/routes/routes.yaml');
        foreach ($routes as $route) {
            if (key_exists('method', $route)) {
                $this->routes[strtolower($route['method'])][$route['path']] = [$route['controller'], $route['function']];
            } else {
                $this->routes['any'][$route['path']] = [$route['controller'], $route['function']];
            }
        }
    }

    /**
     * Resolves user request for given path
     * returns executed callback for current rout
     * @return string|string[]
     */
    public function resolve()
    {

        /**
         * CSRF protection
         */
        if ($this->request->isPost()) {
            $token = $this->request->getCsrfToken();
            if(Application::$app->session->checkCsrfToken($token)) {
                Application::$app->response->setStatusCode(404);
                return $this->renderView('400_');
            }
        }

       $path = $this->request->getPath();
       $method = $this->request->getMethod();

       $callback = ($this->routes[$method][$path] ?? $this->routes['any'][$path]) ?? false;
       if (!$callback) {
           Application::$app->response->setStatusCode(404);
           return $this->renderView('404_');
       }
       if (is_string($callback)) {
           return $this->renderView($callback);
       }
       if (is_array($callback)) {
           if (!class_exists($callback[0])) {
               throw new InvalidArgumentException(
                   "The action controller '$callback[0]' has not been defined.");
           }
           $callback[0] = new $callback[0];
       }
       return call_user_func($callback, $this->request);
    }

    /**
     * Renders view
     * @param string $callback
     * @return string|string[]
     */
    public function renderView(string $callback, $params = [])
    {
        $layoutContent = $this->renderLayout();
        $viewContent = $this->renderJustView($callback, $params);
        return str_replace('{{content}}', $viewContent, $layoutContent);
    }

    /**
     * Returns main layout
     * @return false|string
     */
    public function renderLayout()
    {
        ob_start();
        require_once Application::$ROOT_DIR."/views/layouts/main.php";
        return ob_get_clean();
    }

    /**
     * Returns requested view
     * @param $view
     * @return false|string
     */
    public function renderJustView($view, $params)
    {

        foreach ($params as $key => $value) {
            $$key = $value;
        }
        ob_start();
        require_once Application::$ROOT_DIR."/views/".$view.".php";
        return ob_get_clean();
    }
}