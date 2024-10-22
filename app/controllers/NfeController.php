<?php
namespace app\controllers;

use app\core\Controller;
use app\models\service\ConfiguracaoService;
use app\models\service\NfeService;
use app\models\service\Service;
use app\models\service\ValidaDadosNfeService;
use Exception;
use stdClass;
use ZipArchive;

class NfeController extends Controller{
    public function transmitir(){ 

       // header("content-type: application/json;");
        $dados          = json_decode(file_get_contents("php://input"));       
        $dados_validos  = ValidaDadosNfeService::valida($dados);
    
        if($dados_validos->tem_erro){
            echo json_encode($dados_validos);
            exit;
        }
        $configuracao = $dados_validos->notafiscal->configuracao;       
        $xml = NfeService::gerarNfe($dados_validos->notafiscal);
        if(!$xml->tem_erro){
            $xml_assinado = NfeService::assinarXml($xml->xml, $xml->chave, $configuracao );
            if(!$xml_assinado->tem_erro){
                $envio = NfeService::enviarXml($xml_assinado->xml,$xml->chave, $configuracao,  $dados_validos->notafiscal->ide->nNF);               
                if(!$envio->tem_erro){
                    $i=0;
                    do{
                        sleep(3);
                        $i++;
                        $protocolo = NfeService::consultarPorRecibo($xml_assinado->xml, $xml->chave, $envio->recibo, $configuracao);
                    }while($protocolo->status=="EM_PROCESSAMENTO" && $i<3);
                    
                    echo json_encode($protocolo);
                    exit;
                }else{
                    echo json_encode($envio);
                    exit;
                }
            }else{
                echo json_encode($xml_assinado);
                exit;
            }
        }else{
            echo json_encode($xml);
            exit;
        }
        
    }   
    
    public function danfePeloXml(){
        header('Content-Type: application/json');
        $xml = null;
        try {          
        
            // Verifica se o arquivo foi enviado
            if (!isset($_FILES['arquivo'])) {
                throw new Exception("Arquivo 'arquivo' não foi enviado.");
            }        
            $file = $_FILES['arquivo'];
        
            // Verifica se houve algum erro com o upload
            if ($file['error']) {
                throw new Exception("Erro no upload: " . $file['error']);
            }
        
            // Carrega o arquivo XML
            $xml = file_get_contents($_FILES["arquivo"]["tmp_name"]);
            $danfe = NfeService::danfe($xml);          
            if(!$danfe->tem_erro){
                // Configurar cabeçalhos para download do PDF
                header('Content-Type: application/pdf');
                echo $danfe->pdf;
                exit;

            }else{
                echo json_encode($danfe);
                exit;
            }            
        
        } catch (Exception $e) {
            echo json_encode("erro: " . $e->getMessage());
        }        
  
    }

    public function danfePelaChave($chave){
        if(!$chave){
            throw new Exception("Chave não enviada");            
        }
        try {
            $cnpj = Service::get("nota", "chave", $chave);
            if(!$cnpj){
                throw new Exception("Nenhum arquivo foi encontado com esta chave");
            }
            $caminhoCompleto = "notas/". $cnpj->cnpj ."/xml/nfe/homologacao/autorizadas/" . $chave ."-nfe.xml";       
            if(file_exists($caminhoCompleto)) {
                $xml = file_get_contents($caminhoCompleto);
                $danfe = NfeService::danfe($xml); 
                if(!$danfe->tem_erro){
                    // Configurar cabeçalhos para download do PDF
                    header('Content-Type: application/pdf');
                    echo $danfe->pdf;
                    exit;
    
                }else{
                    echo json_encode($danfe);
                    exit;
                }
            } else {
                throw new Exception("Nenhum arquivo foi encontado com esta chave");
                
            }
        } catch (\Throwable $th) {
            echo json_encode("erro: " . $th->getMessage());
        }        
    }

    public function cancelarNfe(){
        header("content-type: application/json;");
        $dados          = json_decode(file_get_contents("php://input"));
        $mod            = "55";
        $retorno = new stdClass;
        try {
            if(!isset($dados->xNome)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo Xnome");
            }

            if(!isset($dados->cnpj)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo cnpj");
            }

            if(!isset($dados->chave)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo chave");
            }

            if(!isset($dados->UF)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo UF");
            }

            if(!isset($dados->tpAmb)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo tpAmb");
            }

            if(!isset($dados->justificativa)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo justificativa");
            }


            $certificado    = Service::get("certificado_digital","cnpj", $dados->cnpj);
            $configuracao   = ConfiguracaoService::getConfiguracaoNfe($dados->xNome,$dados->cnpj, $dados->UF, $dados->tpAmb, $mod, $certificado);           
            $retorno        =  NfeService::cancelarNfe($dados->justificativa,  $dados->chave,  $configuracao);
        } catch (\Throwable $th) {
            $retorno->tem_erro  = true;
            $retorno->erro      = $th->getMessage();
        }
         echo json_encode($retorno);
    }

    public function cartaCorrecao(){        
        header("content-type: application/json;");
        $dados          = json_decode(file_get_contents("php://input"));
       
        $mod            = "55";
        $retorno = new stdClass;
        try {
            if(!isset($dados->xNome)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo Xnome");
            }

            if(!isset($dados->cnpj)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo cnpj");
            }

            if(!isset($dados->chave)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo chave");
            }

            if(!isset($dados->UF)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo UF");
            }

            if(!isset($dados->tpAmb)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo tpAmb");
            }

            if(!isset($dados->justificativa)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo justificativa");
            }

            if(!isset($dados->sequencia)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo sequencia");
            }
           
            $certificado    = Service::get("certificado_digital","cnpj", $dados->cnpj);

            $configuracao   = ConfiguracaoService::getConfiguracaoNfe($dados->xNome,$dados->cnpj, $dados->UF, $dados->tpAmb, $mod, $certificado);
            $retorno       =  NfeService::cartaCorrecao($dados->justificativa, $dados->sequencia, $dados->chave,  $configuracao);            

        } catch (\Throwable $th) {
            $retorno->tem_erro  = true;
            $retorno->erro      = $th->getMessage();
            echo json_encode($retorno);
            exit;
        }
        echo json_encode($retorno);
    }

    public function imprimircce(){        
        header("content-type: application/json;");
        $dados          = json_decode(file_get_contents("php://input"));
        $retorno = new stdClass;
        try {
            if(!isset($dados->cnpj)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo cnpj");
            }

            if(!isset($dados->chave)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo chave");
            }

            if(!isset($dados->tpAmb)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo tpAmb");
            }
            if(!isset($dados->emitente)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo emitente");
            }

            $retorno = NfeService::cce($dados->tpAmb, $dados->chave, $dados->cnpj, $dados->emitente);
           
            if(!$retorno->tem_erro){
                // Configurar cabeçalhos para download do PDF
                header('Content-Type: application/pdf');
                echo $retorno->pdf;
                exit;

            }else{
                echo json_encode($retorno);
                exit;
            }  

        } catch (\Throwable $th) {
            echo json_encode($retorno);
        }
    }

    public function inutilizarNfe(){
        header("content-type: application/json;");
        $dados          = json_decode(file_get_contents("php://input"));
        $mod            = "55";
        $retorno = new stdClass;
        try {
            if(!isset($dados->xNome)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo Xnome");
            }

            if(!isset($dados->cnpj)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo cnpj");
            }

            if(!isset($dados->UF)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo UF");
            }

            if(!isset($dados->tpAmb)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo tpAmb");
            }

            if(!isset($dados->justificativa)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo justificativa");
            }

            if(!isset($dados->nSerie)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo nSerie");
            }
            if(!isset($dados->nFin)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo nFin");
            }
            $certificado    = Service::get("certificado_digital","cnpj", $dados->cnpj);

            $configuracao   = ConfiguracaoService::getConfiguracaoNfe($dados->xNome,$dados->cnpj, $dados->UF, $dados->tpAmb, $mod, $certificado);
            $retorno        =  NfeService::inutilizar($dados->justificativa, $dados->nSerie, $dados->nIni, $dados->nFin, $configuracao);
        } catch (\Throwable $th) {
            $retorno->tem_erro  = true;
            $retorno->erro      = $th->getMessage();
        }
        echo json_encode($retorno);
    }

    public function baixarXml($chave){
        $nfe            = Service::get("nota","chave", $chave);  
        if(!$nfe){
            echo json_encode("Arquivo não encontrado");
            exit;
        }      
        $pastaAmbiente  = "homologacao";
        $path           = "notas/". $nfe->cnpj."/xml/nfe/". $pastaAmbiente."/autorizadas/" .$chave."-nfe.xml";
        if(!file_exists($path)){
            echo json_encode("Arquivo não encontrado");
            exit;
        }  


        // Cria o arquivo ZIP
        $zip = new ZipArchive();
        $tempDir = sys_get_temp_dir();
        $filename = $tempDir . "/{$chave}.zip";

        if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
            die("Não foi possível abrir o arquivo <$filename>");
        }

        

        // Adiciona o arquivo XML ao ZIP
        $zip->addFile($path, basename($path));
        $zip->close();


        // Verifique se o arquivo existe antes de tentar enviá-lo
        if (file_exists($filename)) {
            // Prepara o cabeçalho para o download do arquivo ZIP
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
            header('Content-Length: ' . filesize($filename));
            ob_end_clean(); // Limpa o buffer de saída
            readfile($filename); // Lê o arquivo e o envia ao usuário
            unlink($filename); // Exclui o arquivo após o envio
        } else {
            die("Falha ao criar o arquivo ZIP.");
        }

    }

  
    
}