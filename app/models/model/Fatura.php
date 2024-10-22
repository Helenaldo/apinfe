<?php

namespace app\models\model;

use app\core\Model;

class Fatura extends Model
{
    
    public $nFat;
    public $vOrig;
    public $vDesc;
    public $vLiq;


    public function setarDados( $data){
        $this->nFat         = $data->nFat ?? null;
        $this->vOrig        = $data->vOrig ?? null;
        $this->vDesc        = $data->vDesc ?? null;
        $this->vLiq         = $data->vLiq ?? null;
    }

    public static function montarXml($nfe, $dados){
        $std = new \stdClass();
        $std->nFat 		    = $dados->nFat;
        $std->vOrig 		= $dados->vOrig ? formataNumero($dados->vOrig)  : null;
        $std->vDesc 		= $dados->vDesc ? formataNumero($dados->vDesc)  : null;
        $std->vLiq 			= $dados->vLiq ? formataNumero($dados->vLiq)  : null;
        $nfe->tagfat($std);
    }
}
