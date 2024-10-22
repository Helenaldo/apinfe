<?php

use app\core\Rota;

$rota = new Rota();

$rota->get('/', 'HomeController@index');
$rota->get('/cliente', 'ClienteController@index');



