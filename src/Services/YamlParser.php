<?php


namespace Meyson\Mvc\src\Services;


class YamlParser
{
    public static function parse($file)
    {
        if(is_file($file)) {
            return yaml_parse_file($file);
        }
        return null;
    }
}