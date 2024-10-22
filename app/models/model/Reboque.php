<?php

namespace app\models\model;

use app\core\Model;

class Reboque extends Model
{
  
    public $placa;
    public $UF;
    public $RNTC;
    public $vagao;
    public $balsa;


    public function setarDados( $data){
        $this->placa        = $data->placa ?? null;
        $this->UF           = $data->UF ?? null;
        $this->RNTC         = $data->RNTC ?? null;
        $this->vagao        = $data->vagao ?? null;
        $this->balsa        = $data->balsa ?? null;
    }

    public static function montarXml($nfe, $dados){
        $std = new \stdClass();
        $std->placa  		= $dados->placa ;
        $std->UF 			= $dados->UF;
        $std->RNTC 		    = $dados->RNTC;
        $nfe->tagreboque($std);

        if($dados->vagao){
            $std = new \stdClass();
            $std->vagao = $dados->vagao;
            $nfe->tagvagao($std);
        }
        if($dados->balsa){
            $std = new \stdClass();
            $std->balsa = $dados->balsa;

            $nfe->tagbalsa($std);
        }
    }
}
