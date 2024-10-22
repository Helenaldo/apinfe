<?php 

namespace app\models\service;
use NFePHP\Common\Certificate;

use app\models\validacao\CertificadoValidacao;

class CertificadoService{
    public static function buscarPeloCnpj($cnpj){
        return Service::get("certificado_digital","cnpj", $cnpj);
    }

    public static function salvar($certificado, $campo, $tabela){
        $validacao = CertificadoValidacao::salvar($certificado);
        return Service::salvar($certificado, $campo, $validacao->listaErros(), $tabela);
    }

    public static function lerCertificado($arquivo, $senha){       
        $retorno = new \stdClass();
        try {
            $detalhe            =  Certificate::readPfx($arquivo, $senha);      

            $cert               = new \stdClass();
            $cert->inicio       = $detalhe->publicKey->validFrom->format('d/m/Y H:i:s');
            $cert->expiracao    = $detalhe->publicKey->validTo->format('d/m/Y H:i:s');
            $cert->serial       = $detalhe->publicKey->serialNumber;
            $cert->id           = $detalhe->publicKey->commonName;

            $retorno->tem_erro  = false;
            $retorno->titulo    = "Certificado Digital";
            $retorno->erro      = "";
            $retorno->retorno   = $cert;
            return $retorno;

        } catch (\Exception $e) {
            $retorno->tem_erro  = true;
            $retorno->titulo    = "Erro ao ler Certificado Digital";
            $retorno->erro      = $e->getMessage();
            return $retorno;
        }
        return $retorno;
    }
}