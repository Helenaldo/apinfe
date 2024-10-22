<?php

namespace app\models\model;

use app\core\Model;

class Autorizado extends Model
{
    
    public $CNPJ;
    public $CPF;


    public function setarDados( $data){
        $this->CNPJ      = $data->CNPJ ?? null;
        $this->CPF       = $data->CPF ?? null;
    }

    public static function montarXml($nfe, $autorizados){
        foreach($autorizados as $autorizado){
            $std        = new \stdClass();
            $std->CNPJ  = $autorizado->CNPJ;
            $std->CPF   = $autorizado->CPF;
            $nfe->tagautXML($std);
        }
    }
}
