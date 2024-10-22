<?php

namespace app\models\model;

use app\core\Model;

class Emitente extends Model
{
   
    public $xNome;
    public $xFant;
    public $IE;
    public $IEST;
    public $IM;
    public $CNAE;
    public $CRT;
    public $CNPJ;
    public $CPF;
    public $xLgr;
    public $nro;
    public $xCpl;
    public $xBairro;
    public $cMun;
    public $xMun;
    public $UF;
    public $CEP;
    public $cPais;
    public $xPais;
    public $fone;

    public function setarDados( $data){
        $this->xNome      = $data->xNome ?? null;
        $this->xFant      = $data->xFant ?? null;
        $this->IE         = $data->IE ?? null;
        $this->IEST       = $data->IEST ?? null;
        $this->IM         = $data->IM ?? null;
        $this->CNAE       = $data->CNAE ?? null;
        $this->CRT        = $data->CRT ?? null;
        $this->CNPJ       = $data->CNPJ ?? null;
        $this->CPF        = $data->CPF ?? null;
        $this->xLgr       = $data->xLgr ?? null;
        $this->nro        = $data->nro ?? null;
        $this->xCpl       = $data->xCpl ?? null;
        $this->xBairro    = $data->xBairro ?? null;
        $this->cMun       = $data->cMun ?? null;
        $this->xMun       = $data->xMun ?? null;
        $this->UF         = $data->UF ?? null;
        $this->CEP        = $data->CEP ?? null;
        $this->cPais      = $data->cPais ?? null;
        $this->xPais      = $data->xPais ?? null;
        $this->fone       = $data->fone ?? null;
    }
    public static function montarXml($nfe, $dados){
        $std = new \stdClass();
        $std->xNome	= tiraAcento(limita_caracteres($dados->xNome,45))	;
        $std->xFant	= tiraAcento($dados->xFant)	;
        $std->IE	= tira_mascara($dados->IE)	    ;
        $std->IEST	= $dados->IEST	;
        $std->IM	= $dados->IM	    ;
        $std->CNAE	= $dados->CNAE	;
        $std->CRT	= $dados->CRT	;

        if($dados->CNPJ):
            $std->CNPJ = tira_mascara($dados->CNPJ);
            $std->CPF = null;
        elseif($dados->CPF):
            $std->CNPJ = NULL;
            $std->CPF  = tira_mascara($dados->CPF);
        else:
            $std->CNPJ = null;
            $std->CPF = null;
        endif;
        $nfe->tagemit($std);

        //endereço do dados
        $std = new \stdClass();
        $std->xLgr		= tiraAcento(limita_caracteres($dados->xLgr,45))	;
        $std->nro		= $dados->nro	    ;
        $std->xCpl		= tiraAcento($dados->xCpl)	;
        $std->xBairro   = tiraAcento(limita_caracteres($dados->xBairro,45))	;
        $std->cMun		= $dados->cMun	;
        $std->xMun		= tiraAcento($dados->xMun)	;
        $std->UF		= $dados->UF		;
        $std->CEP		= tira_mascara($dados->CEP)	    ;
        $std->cPais		= $dados->cPais	;
        $std->xPais		= tiraAcento($dados->xPais)	;
        $std->fone		= $dados->fone    ;
        $nfe->tagenderEmit($std);
    }
}