<?php

namespace app\controllers;

use app\core\Controller;
use app\models\service\CertificadoService;
use app\models\service\ConfiguracaoService;
use app\models\service\DadosService;
use app\models\service\DFeService;
use app\models\service\ValidarDfeService;
use Exception;
use stdClass;
use ZipArchive;

class DfeController extends Controller{    
    public function consulta(){
        $dados   = json_decode(file_get_contents("php://input"));    
           
        $mod     = "55";
        $tpAmb   = 1;
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

            if(!isset($dados->nsuInicial)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo nsu");
            }

            if(!isset($dados->limite)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo nsu");
            }         

            $certificado        = CertificadoService::buscarPeloCnpj($dados->cnpj);
            $configuracao       = ConfiguracaoService::getConfiguracaoNfe($dados->xNome, $dados->cnpj, $dados->UF, $tpAmb, $mod, $certificado);
            $resultado          = DFeService::buscaESalvar($configuracao, $dados->nsuInicial, $dados->cnpj, $dados->limite );               
            echo json_encode($resultado);

        } catch (\Throwable $th) {
            $retorno->tem_erro  = true;
            $retorno->erro      = $th->getMessage();
            echo json_encode($retorno);
        }
    }

    public function download() {
        $dados   = json_decode(file_get_contents("php://input"));  
        $retorno = new stdClass;  
        $filename= "";  
        try {            
            $configuracao = ValidarDfeService::getConfiguracao($dados);
           
            // Caminho do arquivo XML e ZIP
            $pasta      = PASTA_DOWNLOAD . $configuracao->cnpj . "/nfe";
            $arquivoXml = $pasta . "/" . $dados->chave . "-nfe.xml";
    
            // Verifica se o arquivo XML já existe
            if (file_exists($arquivoXml)) {        
                $resultado = DFeService::baixarXml($configuracao->cnpj, $dados->chave);
                $filename  = $resultado->data;                
            }else{
                // Se o arquivo XML não existe, tenta fazer o download da SEFAZ
                $baixou = DFeService::download($configuracao, $dados->chave);
                if ($baixou->tem_erro) {
                    throw new Exception($baixou->erro);
                }
                $resultado = DFeService::baixarXml($configuracao->cnpj, $dados->chave);
                $filename  = $resultado->data; 
            }    

            if ($resultado->tem_erro) {
                throw new Exception($resultado->erro);
            }  
            
            if($filename){
                // Prepara o cabeçalho para o download do arquivo ZIP
                header('Content-Type: application/zip');
                header('Content-Disposition: attachment; filename="' . basename($filename) . '"');
                header('Content-Length: ' . filesize($filename));
                ob_end_clean(); // Limpa o buffer de saída
                readfile($filename); // Lê o arquivo e o envia ao usuário
                unlink($filename); // Exclui o arquivo após o envio
                exit;
            }else{
                $retorno->tem_erro = true;
                $retorno->erro = "Não foi possível baixar o arquivo";
            }

            echo json_encode($retorno);           
    
        } catch (\Exception $e) {
            $retorno->tem_erro = true;
            $retorno->erro = $e->getMessage();
            echo json_encode($retorno);
        }
    }
    
    
    
    

    public function manifestar(){
        $dados   = json_decode(file_get_contents("php://input"));
        $mod     = "55";
        $tpAmb   = 1;
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


            if(!isset($dados->evento)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo evento");
            }
            

            $certificado    = CertificadoService::buscarPeloCnpj($dados->cnpj);
            $configuracao   = ConfiguracaoService::getConfiguracaoNfe($dados->xNome, $dados->cnpj, $dados->UF, $tpAmb, $mod, $certificado);

            $evento         = $dados->evento;

            if($evento == 1){
                $res = DFeService::manifesta($configuracao, $dados->chave);
            }else if($evento == 2){
                $res = DFeService::confirmacao($configuracao, $dados->chave);
            }else if($evento == 3){
                $res = DFeService::desconhecimento($configuracao,$dados->chave);
            }else if($evento == 4){
                $res = DFeService::operacaoNaoRealizada($configuracao,$dados->chave);
            }
            echo json_encode($res);
        } catch (\Throwable $th) {
            $retorno->tem_erro  = true;
            $retorno->erro      = $th->getMessage();
            echo json_encode($retorno);
        }

	}


    public function danfe() {
        $dados   = json_decode(file_get_contents("php://input"));  
        $retorno = new stdClass;  
        $xml    = null;  
        try {            
            $configuracao = ValidarDfeService::getConfiguracao($dados);
           
            // Caminho do arquivo XML e ZIP
            $pasta      = PASTA_DOWNLOAD . $configuracao->cnpj . "/nfe";
            $arquivoXml = $pasta . "/" . $dados->chave . "-nfe.xml";
    
            // Verifica se o arquivo XML já existe
            if (!file_exists($arquivoXml)) { 
                // Se o arquivo XML não existe, tenta fazer o download da SEFAZ
                $baixou = DFeService::download($configuracao, $dados->chave);
                if ($baixou->tem_erro) {
                    throw new Exception($baixou->erro);
                }               
            }    

            if (file_exists($arquivoXml)) {
                $xml = file_get_contents($arquivoXml);
            }            
            
            if($xml){
                $danfe = DFeService::danfe($xml);          
                if(!$danfe->tem_erro){
                    // Configurar cabeçalhos para download do PDF
                    header('Content-Type: application/pdf');
                    echo $danfe->pdf;
                    exit;

                }else{
                    throw new Exception($danfe->erro);
                } 
            }else{
                throw new Exception("XML Não encontrado");
            }     
    
        } catch (\Exception $e) {
            $retorno->tem_erro = true;
            $retorno->erro = $e->getMessage();
            echo json_encode($retorno);
        }
    }




















    public function download2(){
        $dados   = json_decode(file_get_contents("php://input"));
        $mod     = "55";
        $tpAmb   = 1;
        $retorno = new stdClass;
		try{
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


            if(!isset($dados->chave)){
                $retorno->titulo =  "Erro ao ler Campo";
                throw new \Exception("É Obrigatório o envio do campo chave");
            }

            $certificado    = CertificadoService::buscarPeloCnpj($dados->cnpj);
            $configuracao   = ConfiguracaoService::getConfiguracaoNfe($dados->xNome, $dados->cnpj, $dados->UF, $tpAmb, $mod, $certificado);

			$response = DFeService::download($configuracao, $dados->chave);
			json_encode($response);

		}catch(\Exception $e){
			$retorno->tem_erro  = true;
            $retorno->erro      = $e->getMessage();
            json_encode($retorno);
		}
	}
    public function lerDados(){
        DadosService::dados();
    }
    public function lerArquivo(){
        $dados   = json_decode(file_get_contents("php://input"));
        DadosService::arquivo($dados->cnpj, $dados->pasta, $dados->nome);
    }
    public function buscarNsu(){
        $dados   = json_decode(file_get_contents("php://input"));        
        $mod     = "55";
        $tpAmb   = 1;
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

            $certificado        = CertificadoService::buscarPeloCnpj($dados->cnpj);
            $configuracao       = ConfiguracaoService::getConfiguracaoNfe($dados->xNome, $dados->cnpj, $dados->UF, $tpAmb, $mod, $certificado);
            $resultado          = DFeService::buscarNsu($configuracao); 
            i($resultado);  

            if($resultado->tem_erro){
                throw new \Exception("É Obrigatório o envio do campo nsu");
            }
            echo json_encode($resultado);

        } catch (\Throwable $th) {
            $retorno->tem_erro  = true;
            $retorno->erro      = $th->getMessage();
            echo json_encode($retorno);
        }
    }

}
