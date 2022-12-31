<?php


namespace app\core;


class Session
{
    public function setCsrfToken()
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            $_SESSION['csrf_token_expire'] = time() + 3600;
        } else if ($_SESSION['csrf_token_expire'] <= time()) {
            unset($_SESSION['csrf_token']);
            unset($_SESSION['csrf_token_expire']);
        }
    }

    public function checkCsrfToken($token)
    {
        return $token === $_SESSION['csrf_token'];
    }
}