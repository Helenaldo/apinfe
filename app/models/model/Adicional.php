<?php

namespace app\models\model;

use app\core\Model;

class Adicional extends Model
{
    
    public $infCpl;
    public $infAdFisco;


    public function setarDados( $data){
        $this->infCpl           = $data->infCpl ?? null;
        $this->infAdFisco       = $data->infAdFisco ?? null;
    }

    public static function montarXml($nfe, $dados){
        $std               = new \stdClass();
        $std->infAdFisco   = $dados->infAdFisco    ;
        $std->infCpl       = $dados->infCpl     ;
        $nfe->taginfAdic($std);
    }
}
