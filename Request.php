<?php


namespace app\core;


class Request
{

    public static string $GET_METHOD = 'get';
    public static string $POST_METHOD = 'post';

    /**
     * Returns current path without parameters
     * @return string
     */
    public function getPath()
    {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $pos = strpos($path, '?');
        if ($pos === false) {
            return $path;
        }
        $path = substr($path,0,$pos);
        return $path;
    }

    /**
     * Returns current method
     * @return mixed
     */
    public function getMethod()
    {
        $method = $_SERVER['REQUEST_METHOD'];
        return strtolower($method);
    }

    /**
     * @return bool
     */
    public function isPost(): bool
    {
        return $this->getMethod() === self::$POST_METHOD;
    }

    /**
     * @return bool
     */
    public function isGet(): bool
    {
        return $this->getMethod() === self::$GET_METHOD;
    }

    /**
     * Returns data of current request
     * @return array
     */
    public function getData(): array
    {
        $data = [];
        if ($this->isGet()) {
            foreach ($_GET as $key => $value) {
                $data[$key] = $value;
            }
        }

        if ($this->isPost()) {
            foreach ($_POST as $key => $value) {
                $data[$key] = filter_input(INPUT_POST, $key, FILTER_SANITIZE_SPECIAL_CHARS);
            }
        }

        return $data;
    }

    public function getCsrfToken()
    {
        return $_POST['csrf_token'] ?? null;
    }
}