<?php

namespace app\models\model;

use app\core\Model;

class Intermediador extends Model
{
    
    public $cnpjIntermed;
    public $idCadIntTran;


    public function setarDados( $data){
        $this->cnpjIntermed     = $data->cnpjIntermed ?? null;
        $this->idCadIntTran       = $data->idCadIntTran ?? null;
    }

    public static function montarXml($nfe, $dados){
        $std                    = new \stdClass();
        $std->CNPJ              = $dados->cnpjIntermed   ;
        $std->idCadIntTran      = $dados->idCadIntTran    ;       
        $nfe->tagIntermed($std);
    }
}