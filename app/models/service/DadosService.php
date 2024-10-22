<?php

namespace app\models\service;

use Exception;
use NFePHP\NFe\Common\Standardize;
use stdClass;

class DadosService{
	public static function dados(){	

		// Caminho para o arquivo que você enviou (ajuste conforme necessário)
		$caminhoArquivo = realpath('./'). '/chave1.txt';		

		// Estrutura do objeto fornecido
		$data = [
			'cStat' 					=> 138,
			'xMotivo' 					=> 'Documento(s) localizado(s)',
			'dhResp' 					=> '2024-10-15T14:24:03-03:00',
			'ultNSU' 					=> '000000000151589',
			'maxNSU' 					=> '000000000166787',
			'lote' => [
				'schemaTypeInfo' 		=> null,
				'tagName' 				=> 'loteDistDFeInt',
				'firstElementChild' 	=> '(object value omitted)',
				'lastElementChild' 		=> '(object value omitted)',
				'childElementCount' 	=> 50,
				'previousElementSibling'=> '(object value omitted)',
				'nextElementSibling' 	=> null,
				'nodeName' 				=> 'loteDistDFeInt',
				'nodeValue' 			=> self::lerNodeValueDoArquivo($caminhoArquivo)
			]
		];

		// Convertendo para JSON
		$json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
		header('Content-Type: application/json');
		echo $json;
				
	}

	// Função para ler o conteúdo do arquivo e decodificá-lo
	public static function lerNodeValueDoArquivo($caminho) {
		$conteudo = file_get_contents($caminho);
		if ($conteudo === false) {
			return 'Erro ao ler o arquivo.';
		}

		$decoded = base64_decode($conteudo);
		if ($decoded === false) {
			return 'Erro na decodificação Base64.';
		}

		$uncompressed = @gzdecode($decoded);
		return $uncompressed ?: 'Erro ao descomprimir GZIP.';
	}

	public static function arquivo($cnpj, $pasta, $nome){	
		if($pasta){
			$caminhoArquivo = PASTA_DOWNLOAD . $cnpj ."/" .$pasta ."/" .$nome .".xml" ;
		}else{
			$caminhoArquivo = PASTA_DOWNLOAD . $cnpj ."/"  .$nome .".xml" ;
		}
		$conteudo 		= file_get_contents($caminhoArquivo);		
		$xml 			= simplexml_load_string($conteudo);
		//i($xml);
		$chave 			= DFeService::extrairChaveAcesso($xml);
		//i($chave);
		$atributosGenericos = DFeService::extrairAtributosGenericos($xml);
		i($atributosGenericos);
	}





}