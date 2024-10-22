<?php
namespace app\controllers;

use app\core\Controller;
use app\core\Flash;
use app\models\service\CertificadoService;
use app\models\service\Service;
use Exception;
use NFePHP\Common\Certificate;
use stdClass;

class CertificadodigitalController extends Controller{

    public function ler(){

try {
    // Verificando se o arquivo e a senha foram enviados
    if (!isset($_FILES['arquivo']) || $_FILES['arquivo']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception("Erro no upload do certificado: " . $_FILES['arquivo']['error']);
    }

    $arquivoTemporario = $_FILES['arquivo']['tmp_name'];
    $senhaCertificado = $_POST['senha'] ?? null;

    // Verificando se a senha foi enviada
    if (is_null($senhaCertificado)) {
        throw new Exception("A senha do certificado não foi enviada.");
    }

    $conteudoCertificado = file_get_contents($arquivoTemporario);
    $certInfo = [];

    // Tentando ler o certificado
    $resultado = openssl_pkcs12_read($conteudoCertificado, $certInfo, $senhaCertificado);

    if (!$resultado) {
        $erro = error_get_last();
        throw new Exception("Erro do OpenSSL: " . ($erro['message'] ?? 'Erro desconhecido.'));
    }

    // Garantindo que $certInfo não seja nulo antes de acessar seus índices
    if (!isset($certInfo['cert'])) {
        throw new Exception("Certificado inválido ou não encontrado.");
    }

    $informacoes = openssl_x509_parse($certInfo['cert']);

    echo json_encode([
        'status' => 'sucesso',
        'dados_certificado' => [
            'emitido_para' => $informacoes['subject']['CN'] ?? 'Desconhecido',
            'valido_de'    => date('Y-m-d', $informacoes['validFrom_time_t']),
            'valido_ate'   => date('Y-m-d', $informacoes['validTo_time_t']),
            'emissor'      => $informacoes['issuer']['CN'] ?? 'Desconhecido',
        ],
    ]);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'erro',
        'mensagem' => "Erro: " . $e->getMessage(),
    ]);
}

    }

    public function salvar(){        
        $dados =(object) $_POST; 
       
         $retorno = new stdClass;
         try {
            $certificado                    = new \stdClass();
            $certificado->cnpj              = $dados->cnpj ?? null;
            $certificado->senha             = $dados->senha?? null;
            $certificado->csc               = $dados->csc ?? null;
            $certificado->csc_id            = $dados->csc_id ?? null;

           echo "veio";

            // Verifica se o arquivo foi enviado
            if (isset($_FILES['arquivo'])) { 
                $certificado->arquivo_binario = file_get_contents($_FILES["arquivo"]["tmp_name"]);
           
                $resultado = CertificadoService::lerCertificado(file_get_contents($_FILES["arquivo"]["tmp_name"]), $certificado->senha);
                
                if(!$resultado->tem_erro){
                    $certificado->inicio             = $resultado->retorno->inicio;
                    $certificado->expiracao          = $resultado->retorno->expiracao;
                    $certificado->serial             = $resultado->retorno->serial;
                    $certificado->identificador      = $resultado->retorno->id;
                }else{
                    throw new \Exception($resultado->erro);
                }             
            }   

            $tem = Service::get("certificado_digital","cnpj",$certificado->cnpj);
            $certificado->id_certificado_digital = $tem->id_certificado_digital ?? null;

            $salvar = CertificadoService::salvar($certificado, "id_certificado_digital", "certificado_digital");   
            if(!$salvar){
                throw new \Exception("Erro: ". Flash::getErro()[0] ?? "erro na operação");
            }        
          
            $retorno->tem_erro  = false;
            $retorno->erro      = "";
            $retorno->retorno   = "ok";
            header("content-type: application/json;");
		    echo json_encode($retorno);
         } catch (\Throwable $th) {
            $retorno->tem_erro = true;
            $retorno->erro = $th->getMessage();
            header("content-type: application/json;");
		    echo json_encode($retorno);
         }
    }
    
}
