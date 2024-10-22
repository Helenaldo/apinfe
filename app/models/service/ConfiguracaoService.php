<?php
namespace app\models\service;

use app\models\model\Configuracao;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;

class ConfiguracaoService{

    public static function getConfiguracaoNfe($xNome,$CNPJ, $UF, $tpAmb, $mod, $certificado){
        $arr = [
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb"       => intVal($tpAmb),
            "razaosocial" => $xNome,
            "cnpj"        => $CNPJ,
            "siglaUF"     => $UF,
            "schemes"     => "PL_009_V4",
            "versao"      => '4.00',
            "tokenIBPT"   => "",
            "CSC"         => "",
            "CSCid"       => "",
            "proxyConf"   => [
                "proxyIp"   => "",
                "proxyPort" => "",
                "proxyUser" => "",
                "proxyPass" => ""
            ]
        ];
        $objeto = new Configuracao();
        $configJson                 = json_encode($arr);
        $certificado_digital        = $certificado->arquivo_binario;
        $objeto->cnpj               = $CNPJ;
        $objeto->tpAmb              = $tpAmb;
        $objeto->tools              = new Tools($configJson, Certificate::readPfx($certificado_digital, $certificado->senha));
        $objeto->pastaAmbiente      = ($tpAmb == "1") ? "producao" : "homologacao";
        $objeto->pastaEmpresa       = $CNPJ;
        $objeto->tools->model($mod);
        return $objeto;
    }


}

