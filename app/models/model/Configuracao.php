<?php

namespace app\models\model;

use app\core\Model;

class Configuracao extends Model
{
    
    public $cnpj;
    public $certificado;
    public $tpAmb;
    public $tools;
    public $pastaAmbiente;
    public $pastaEmpresa;


    public function setarDados( $data){
        $this->cnpj             = $data->cnpj ?? null;
        $this->certificado      = $data->certificado ?? null;
        $this->tpAmb            = $data->tpAmb ?? null;
        $this->tools            = $data->tools ?? null;
        $this->pastaAmbiente    = $data->pastaAmbiente ?? null;
        $this->pastaEmpresa     = $data->pastaEmpresa ?? null;
    }
}