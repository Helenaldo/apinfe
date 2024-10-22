<?php
namespace app\models\validacao;

use app\core\Validacao;
use app\models\service\Service;

class CertificadoValidacao {

    public static function salvar($certificado){
        $validacao = new Validacao();
        
        $validacao->setData("cnpj", $certificado->cnpj);
        $validacao->setData("senha", $certificado->senha);
        
        //fazendo a validação
        $validacao->getData("cnpj")->isVazio()->isMinimo(5);
        $validacao->getData("senha")->isVazio();   
      

        return $validacao;
        
    }
}