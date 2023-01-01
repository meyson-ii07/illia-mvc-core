<?php


namespace Meyson\IlliaMvcCore;


class Response
{

    /**
     * Sets status code of response
     * @param int $code
     */
    public function setStatusCode(int $code)
    {
        http_response_code($code);
    }
}