<?php

namespace app\models\service;

use stdClass;

class ValidarDfeService{    
public static function getConfiguracao($dados){
    $mod     = "55";
    $tpAmb   = 1;
    // Verificações dos campos obrigatórios
    if (!isset($dados->xNome)) {
        throw new \Exception("É Obrigatório o envio do campo Xnome");
    }

    if (!isset($dados->cnpj)) {
        throw new \Exception("É Obrigatório o envio do campo CNPJ");
    }

    if (!isset($dados->UF)) {
        throw new \Exception("É Obrigatório o envio do campo UF");
    }

    if (!isset($dados->chave)) {
        throw new \Exception("É Obrigatório o envio do campo chave");
    }

     // Configuração da NFe e diretório de download
    $certificado  = CertificadoService::buscarPeloCnpj($dados->cnpj);
    $configuracao = ConfiguracaoService::getConfiguracaoNfe($dados->xNome, $dados->cnpj, $dados->UF, $tpAmb, $mod, $certificado);

    return $configuracao;
}


}
