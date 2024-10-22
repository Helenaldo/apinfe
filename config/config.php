<?php
define("ROTA_MANUAL",false);
/*
define("SERVIDOR", "localhost");
define("USUARIO", "pleni789_gratis");
define("BANCO", "pleni789_apinfephp");
define("SENHA", "Sucesso4152@");
define("CHARSET","UTF8");*/

define("SERVIDOR", "localhost");
define("BANCO", "borrao_dfe_api");
define("USUARIO", "root");
define("SENHA", "");
define("CHARSET","UTF8");


define('CONTROLLER_PADRAO', 'home');
define('METODO_PADRAO', 'index');
define('NAMESPACE_CONTROLLER', 'app\\controllers\\');
define('TIMEZONE',"America/Fortaleza");
define('CAMINHO'            , realpath('./'));
define("TITULO_SITE","mjailton-ligando vc ao mundo do conhecimento");

//define('URL_BASE', 'http://' . $_SERVER["HTTP_HOST"].'/metodoagora/mjapi');
define('URL_BASE', '/metodoagora/pacote-fiscal/apinfe-php/');
define('URL_IMAGEM', "http://". $_SERVER['HTTP_HOST'] . "/estrutura_mvc/UP/");
define('PASTA_DOWNLOAD', "NOTAS_BAIXADAS/");

define("SESSION_LOGIN","usuario_logado");

$config_upload["verifica_extensao"] = false;
$config_upload["extensoes"]         = array(".gif",".jpeg", ".png", ".bmp", ".jpg");
$config_upload["verifica_tamanho"]  = true;
$config_upload["tamanho"]           = 3097152;
$config_upload["caminho_absoluto"]  = realpath('./'). '/';
$config_upload["renomeia"]          = true;
