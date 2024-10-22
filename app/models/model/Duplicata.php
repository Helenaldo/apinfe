<?php

namespace app\models\model;

use app\core\Model;

class Duplicata extends Model
{
    
    protected $fillables = [ "nDup","dVenc", "vDup" ];
    public $nDup;
    public $dVenc;
    public $vDup;


    public function setarDados( $data){
        $this->nDup         = $data->nDup ?? null;
        $this->dVenc        = $data->dVenc ?? null;
        $this->vDup         = $data->vDup ?? null;
    }

    public static function montarXml($nfe, $dados){
        $std = new \stdClass();
        $std->nDup 		= $dados->nDup;
        $std->dVenc 	= $dados->dVenc;
        $std->vDup 		= $dados->vDup ? formataNumero($dados->vDup)  : null;
        $nfe->tagdup($std);
    }
}