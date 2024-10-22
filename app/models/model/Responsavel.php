<?php

namespace app\models\model;

use app\core\Model;

class Responsavel extends Model
{
    
    public $CNPJ;
    public $xContato;
    public $email;
    public $fone;
    public $CSRT;
    public $idCSRT;


    public function setarDados( $data){
        $this->CNPJ         = $data->CNPJ ?? null;
        $this->xContato     = $data->xContato ?? null;
        $this->email        = $data->email ?? null;
        $this->fone         = $data->fone ?? null;
        $this->CSRT         = $data->CSRT ?? null;
        $this->idCSRT       = $data->idCSRT ?? null;
    }

    public static function montarXml($nfe, $dados){
        $std                  = new \stdClass();
        $std->CNPJ            = isset($dados->CNPJ) ? $dados->CNPJ :null     ;
        $std->xContato        = isset($dados->xContato) ? $dados->xContato :null     ;
        $std->email           = isset($dados->email) ? $dados->email :null     ;
        $std->fone            = isset($dados->fone) ? $dados->fone :null      ;
        $std->CSRT            = isset($dados->CSRT) ? $dados->CSRT :null     ;
        $std->idCSRT          = isset($dados->idCSRT) ? $dados->idCSRT  :null     ;
        $nfe->taginfRespTec($std);
    }
}
