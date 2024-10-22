<?php
namespace app\core;

use Exception;

class Request {    
    public static function getUrl() {
        $url = filter_input(INPUT_GET, 'request');  
        $url = str_replace(URL_BASE, '', $url);      
        return '/'.$url;
    }

    public static function getTipoMetodo() {
        return strtolower($_SERVER['REQUEST_METHOD']);
    }    


}