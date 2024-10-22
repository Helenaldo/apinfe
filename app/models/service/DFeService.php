<?php

namespace app\models\service;

use Exception;
use NFePHP\DA\NFe\Danfe;
use NFePHP\NFe\Common\Standardize;
use stdClass;
use ZipArchive;

class DFeService{
	public static function buscaESalvar($configuracao, $nsu, $cnpj, $loopLimit = 2) {
		$retorno = new stdClass;
		$ultNSU    = $nsu;
		$maxNSU    = $ultNSU;
		$iCount    = 0;
		$last      = "";
		$arrayDocs = [];
	
		// Criar diretórios fora do loop para evitar repetição
		$nomePastaBase = PASTA_DOWNLOAD . $cnpj . "/";
		if (!is_dir($nomePastaBase)) {
			mkdir($nomePastaBase, 0777, true);
		}
	
		while ($ultNSU <= $maxNSU) {
			$iCount++;
			if ($iCount >= $loopLimit) {
				break;
			}
			try {
				$resp = $configuracao->tools->sefazDistDFe($ultNSU);
				$dom = new \DOMDocument();
				$dom->loadXML($resp);
				$node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
				$cStat = $node->getElementsByTagName('cStat')->item(0)->nodeValue;
				$xMotivo = $node->getElementsByTagName('xMotivo')->item(0)->nodeValue;
				$ultNSU = $node->getElementsByTagName('ultNSU')->item(0)->nodeValue;
				$maxNSU = $node->getElementsByTagName('maxNSU')->item(0)->nodeValue;
				$lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);
	
				// Atualizar dados no banco
				$obj = new stdClass;
				$obj->cnpj 	 = $cnpj;
				$obj->ultnsu = $ultNSU;
				$obj->maxnsu = $maxNSU;
				$obj->cStat  = $cStat;
				$obj->iCount = $iCount;
				Service::editar(objToArray($obj), "cnpj", "certificado_digital");
	
				// Verificar status de retorno
				if ($cStat == '137' ) {
					$retorno->tem_erro = false;
					$retorno->data = count($arrayDocs) . " arquivos baixados";
					continue;
				}

				if (in_array($cStat, ['656', '999'])) {
					$retorno->tem_erro  = true;
					$retorno->erro 		= "erro: " . $xMotivo . " - " . count($arrayDocs) . " arquivos baixados";;
					return $retorno;
				}
	
				if (empty($lote) || $last == $ultNSU) {
					continue;
				}
	
				$last = $ultNSU;
				$docs = $lote->getElementsByTagName('docZip');
				sleep(2);
				foreach ($docs as $doc) {
					$numnsu = $doc->getAttribute('NSU');
					$schema = $doc->getAttribute('schema');
					$content = gzdecode(base64_decode($doc->nodeValue));
					$xml = simplexml_load_string($content);
	
					// Identificar tipo de documento
					$tipoDoc = self::identificarTipoDocumento($schema);				
					
	
					$chave = self::extrairChaveAcesso($xml);
					$atributosGenericos = self::extrairAtributosGenericos($xml);
					$temp = array_merge([
						'cnpj' 		=> $cnpj,
						'chave' 	=> $chave,
						'nsu' 		=> $numnsu,
						'ultnsu' 	=> $ultNSU,
						'maxnsu' 	=> $maxNSU,
						'tipo' 		=> $tipoDoc,
						'fatura_salva' => false,
						'sequencia_evento' => 0,
						'data_baixado' => hoje(),
						'hora_baixado' => agora(),
					], $atributosGenericos);
	
					// Criar pasta para o tipo de documento, se necessário
					$nomePasta = $nomePastaBase . $tipoDoc . "/";
					$retorno->teste = "veio";
					if (!is_dir($nomePasta)) {
						mkdir($nomePasta, 0777, true);
					}	
					// Função de salvar documento
					$nomeArquivo = $nomePasta . $chave ."-" .$tipoDoc .".xml";
					file_put_contents($nomeArquivo, $content);
					Service::inserir($temp, "dfe");
	
					// Atualizar NSU no banco
					$obj = new stdClass;
					$obj->cnpj = $cnpj;
					$obj->nsu = $numnsu;
					Service::editar(objToArray($obj), "cnpj", "certificado_digital");	
					array_push($arrayDocs, $temp);
				}				
	
			} catch (\Exception $e) {
				$retorno->tem_erro = true;
				$retorno->erro = "erro: " . $e->getMessage();
				return $retorno;
			}
		}

		$retorno->tem_erro = false;
		$retorno->data 		= count($arrayDocs) . " arquivos baixados";
		return $retorno;
	}
	
	
	
	public static function extrairAtributosGenericos($xml) {
		$dados = [];
	
		// Função auxiliar para verificar se o nó existe e retornar seu valor
		$obterValor = function($xml, $caminho) {
			$valor = $xml->xpath($caminho);
			return isset($valor[0]) ? (string)$valor[0] : null;
		};
	
		// CNPJ (presente em NF-e e CT-e)
		$dados['documento'] = $obterValor($xml, '//doc:CNPJ | //CNPJ');
		
		// Nome (pode estar em várias tags, dependendo do documento)
		$dados['nome'] = $obterValor($xml, '//doc:xNome | //xNome');
		
		// Data de Emissão (NF-e, CT-e, MDFe)
		$dados['data_emissao'] = $obterValor($xml, '//doc:dhEmi | //dhEmi | //doc:emit/dhEmi');
		$data = converterDataHora($dados['data_emissao']);
		$dados['data'] = $data['data'];
		$dados['hora'] = $data['hora'];
		
		// Valor Total (NF-e, CT-e)
		$dados['valor'] = (float) $obterValor($xml, '//doc:vNF | //vNF');
		
		// Número do Protocolo (eventos ou documentos com numeração de protocolo)
		$dados['num_prot'] = $obterValor($xml, '//doc:nProt | //nProt');
		
		// Adicionar outros campos conforme necessário
	
		return $dados;
	}


	public static function identificarTipoDocumento($schema) {
		$schema = strtolower($schema); // Converte para minúsculo para evitar problemas de case	
		if (strpos($schema, 'procnfe') !== false) {
			return 'nfe';
		} elseif (strpos($schema, 'proceventonfe') !== false) {
			return 'evento_nfe';
		} elseif (strpos($schema, 'proccte') !== false) {
			return 'cte';
		} elseif (strpos($schema, 'proceventocte') !== false) {
			return 'evento_cte';
		} elseif (strpos($schema, 'procmdfe') !== false) {
			return 'mdfe';
		} elseif (strpos($schema, 'proceventomdfe') !== false) {
			return 'evento_mdfe';
		} elseif (strpos($schema, 'procgnre') !== false) {
			return 'gnre';
		} elseif (strpos($schema, 'proccc-e') !== false) {
			return 'cce';
		} elseif (strpos($schema, 'resnfe') !== false) {
			return 'resumo_nfe'; // Adiciona o caso para resNFe
		} else {
			return 'outro';
		}
	}


	public static function download($configuracao, $chave){
    $retorno = new stdClass;
    try {
        // Caminho para salvar o XML
        $pasta = PASTA_DOWNLOAD . $configuracao->cnpj . "/nfe";
        if (!is_dir($pasta)) {
            mkdir($pasta, 0777, true);
        }

        // Realiza o download da NFe completa na SEFAZ
        $response = $configuracao->tools->sefazDownload($chave, 'NFe', '0'); // Especificando que deseja o XML completo da NFe

        $standardize = new Standardize();
        $std = $standardize->toStd($response);

        // Verifica se o download foi bem-sucedido (cStat == 138 ou similar)
        if (isset($std->cStat) && $std->cStat == '138') { // Documento localizado para download
            if (isset($std->loteDistDFeInt)) {
                // Verifica se `docZip` é um array ou objeto
                $docZipItems = is_array($std->loteDistDFeInt->docZip) ? $std->loteDistDFeInt->docZip : [$std->loteDistDFeInt->docZip];

                // Itera sobre os itens, mesmo que seja um único objeto
                foreach ($docZipItems as $docZip) {
                    // Decodifica e descompacta o conteúdo ZIP
                    $xmlZip = base64_decode($docZip);
                    $xml = gzdecode($xmlZip);

                    // Verifica se o XML é uma NFe completa (procNFe) e não uma nota simplificada (resNFe)
                    if (strpos($xml, '<resNFe') === false) {
                        // Salva o XML completo no arquivo
                        $nomeArquivo = $pasta . "/" . $chave . "-nfe.xml";
                        file_put_contents($nomeArquivo, $xml);

                        $retorno->tem_erro = false;
                        $retorno->erro = "";
                    } else {
                        $retorno->tem_erro = true;
                        $retorno->erro = "A Sefaz só disponibilizou a versão simplificada, possivelmente você precisa fazer o manifesto nesta nota.";
                    }
                }
            } else {
                $retorno->tem_erro = true;
                $retorno->erro = "Nenhum lote de distribuição encontrado para a chave: {$chave}.";
            }
        } else {
            $retorno->tem_erro = true;
            $retorno->erro = "Erro ao realizar o download. Status: {$std->cStat}, Motivo: {$std->xMotivo}";
        }
    } catch (\Exception $e) {
        $retorno->tem_erro = true;
        $retorno->erro = "Erro: " . $e->getMessage();
    }

    return $retorno;
}


public static function manifesta($configuracao, $chave){
	$retorno = new stdClass;
    try {
        // Configurações da SEFAZ
        $tools = $configuracao->tools;

        // Manifesto de Ciência da Operação (Tipo de Evento: 210210)
        $response = $tools->sefazManifesta($chave, '210210', 'Tenho ciência da operação', 1);

        $standardize = new Standardize();
        $std = $standardize->toStd($response);

        // Verifica o status do evento
        if ($std->cStat == '135') {
            $retorno->tem_erro = false;
            $retorno->data = "Manifesto de ciência da operação registrado com sucesso.";
        } else {
            $retorno->tem_erro = true;
            $retorno->erro = "Erro no manifesto de ciência da operação. Status: {$std->cStat}, Motivo: {$std->xMotivo}";
        }
    } catch (\Exception $e) {
        $retorno->tem_erro = true;
        $retorno->erro = "Erro: " . $e->getMessage();
    }

    return $retorno;
}

public static function confirmacao($configuracao, $chave){
	$retorno = new stdClass;
    try {
        // Configurações da SEFAZ
        $tools = $configuracao->tools;
        // Confirmação da Operação (Tipo de Evento: 210200)
        $response = $tools->sefazManifesta($chave, '210200', 'Confirmo a operação', 1);

        $standardize = new Standardize();
        $std = $standardize->toStd($response);

        // Verifica o status do evento
        if ($std->cStat == '135') {
            $retorno->tem_erro = false;
            $retorno->data 		= "Confirmação da operação registrada com sucesso.";
        } else {
            $retorno->tem_erro = true;
            $retorno->erro = "Erro na confirmação da operação. Status: {$std->cStat}, Motivo: {$std->xMotivo}";
        }
    } catch (\Exception $e) {
        $retorno->tem_erro = true;
        $retorno->erro = "Erro: " . $e->getMessage();
    }
    return $retorno;	
}
public static function desconhecimento($configuracao, $chave){
	$retorno = new stdClass;
    try {
        // Configurações da SEFAZ
        $tools = $configuracao->tools;

        // Desconhecimento da Operação (Tipo de Evento: 210220)
        $response = $tools->sefazManifesta($chave, '210220', 'Desconheço a operação', 1);

        $standardize = new Standardize();
        $std = $standardize->toStd($response);

        // Verifica o status do evento
        if ($std->cStat == '135') {
            $retorno->tem_erro = false;
            $retorno->data = "Desconhecimento da operação registrado com sucesso.";
        } else {
            $retorno->tem_erro = true;
            $retorno->erro = "Erro no desconhecimento da operação. Status: {$std->cStat}, Motivo: {$std->xMotivo}";
        }
    } catch (\Exception $e) {
        $retorno->tem_erro = true;
        $retorno->erro = "Erro: " . $e->getMessage();
    }

    return $retorno;
}

public static function operacaoNaoRealizada($configuracao, $chave){
	$retorno = new stdClass;
    try {
        // Configurações da SEFAZ
        $tools = $configuracao->tools;

        // Evento de Operação Não Realizada (código: 210240)
        $justificativa = "A operação não foi realizada."; // Pode ser personalizada
        $sequenciaEvento = 1; // Sequência do evento, geralmente é 1
        $tipoEvento = '210240'; // Código do evento de Operação Não Realizada

        // Registrar o evento de Operação Não Realizada
        $response = $tools->sefazManifesta($chave, $tipoEvento, $justificativa, $sequenciaEvento);

        // Padronizando a resposta
        $standardize = new Standardize();
        $std = $standardize->toStd($response);

        // Verifica o status do evento
        if ($std->cStat == '135') {
            $retorno->tem_erro = false;
            $retorno->data = "Operação não realizada registrada com sucesso.";
        } else {
            $retorno->tem_erro = true;
            $retorno->erro = "Erro ao registrar operação não realizada. Status: {$std->cStat}, Motivo: {$std->xMotivo}";
        }
    } catch (\Exception $e) {
        $retorno->tem_erro = true;
        $retorno->erro = "Erro: " . $e->getMessage();
    }
    return $retorno;
}


public static function baixarXml($cnpj, $chave) {
	$retorno = new stdClass;

	$path = PASTA_DOWNLOAD . $cnpj . "/nfe/" . $chave . "-nfe.xml";
	
	if (!file_exists($path)) {
		$retorno->tem_erro = true;
		$retorno->erro = "Arquivo XML não encontrado";
	}

	// Cria o arquivo ZIP
	$zip = new ZipArchive();
	$tempDir = sys_get_temp_dir();
	$filename = $tempDir . "/{$chave}.zip";

	// Verificação: diretório temporário existe?
	if (!is_writable($tempDir)) {
		$retorno->tem_erro = true;
		$retorno->erro = "Diretório temporário não é gravável";
	}

	// Tenta abrir o ZIP
	if ($zip->open($filename, ZipArchive::CREATE) !== TRUE) {
		$retorno->tem_erro = true;
		$retorno->erro = "Falha ao criar o arquivo ZIP";
	}

	// Tenta adicionar o arquivo XML ao ZIP
	if (!$zip->addFile($path, basename($path))) {
		$zip->close();
		$retorno->tem_erro = true;
		$retorno->erro = "Falha ao adicionar o arquivo XML ao ZIP";
	}

	// Fecha o arquivo ZIP
	$zip->close();

	// Verifica se o arquivo ZIP foi criado com sucesso
	if (file_exists($filename)) {
		$retorno->tem_erro 	= false;
		$retorno->data 		= $filename;
	} else {		
		$retorno->tem_erro = true;
		$retorno->erro = "Arquivo ZIP não foi criado";
	}

	return $retorno;
}

public static function danfe($xml){
	$retorno = new \stdClass();
	try {
	  // $logo = 'data://text/plain;base64,'. base64_encode(file_get_contents(realpath(__DIR__ . '/../images/tulipas.png')));
		//$logo = realpath(__DIR__ . '/../images/tulipas.png');

		$danfe = new Danfe($xml);
		
		$danfe->exibirTextoFatura = true;
		$danfe->exibirPIS = true;
	   // $danfe->exibirCOFINS = true;
		$danfe->exibirIcmsInterestadual = false;
		$danfe->exibirValorTributos = true;
		$danfe->descProdInfoComplemento = false;
		$danfe->exibirNumeroItemPedido = false;
		$danfe->setOcultarUnidadeTributavel(true);
		$danfe->obsContShow(false);
		$danfe->printParameters(
			$orientacao = 'P',
			$papel = 'A4',
			$margSup = 2,
			$margEsq = 2
			);
	   // $danfe->logoParameters($logo, $logoAlign = 'C', $mode_bw = false);
		$danfe->setDefaultFont($font = 'times');
		$danfe->setDefaultDecimalPlaces(4);
		$danfe->debugMode(false);
		$danfe->creditsIntegratorFooter('mjailton Sistemas - mjailton.com.br');            
		//Gera o PDF
		$pdf = $danfe->render();

		$retorno->tem_erro  = false;
		$retorno->titulo    = "Pdf gerado com sucesso";
		$retorno->erro      = "";
		$retorno->pdf       = $pdf;
		return $retorno;
	} catch (\Exception $e) {
		$retorno->tem_erro  = true;
		$retorno->titulo    = "Erro gerar o PDF";
		$retorno->erro      = $e->getMessage();
		$retorno->pdf       = NULL;
		return $retorno;
	}
	return $retorno;
}

































private static function salvarDocumento($nomePasta, $tipoDoc, $chave, $numnsu, $content, $dados) {	
	$nomeArquivo = $nomePasta . $chave ."-" .$tipoDoc. "-" .$numnsu .".xml";	
	// Inserção no banco
	if (in_array($tipoDoc, ['nfe', 'cte', 'mdfe', 'gnre', 'cce','resumo_nfe'])) {
		$nomeArquivo = $nomePasta . $chave ."-" .$tipoDoc .".xml";
		file_put_contents($nomeArquivo, $content);

		Service::inserir($dados, "dfe");
	} else {
		file_put_contents($nomeArquivo, $content);
		Service::inserir($dados, "dfe");
	}
}
	public static function buscaESalvar2($configuracao, $nsu, $cnpj, $loopLimit=2 ) {
		$ultNSU     = $nsu;
		$maxNSU     = $ultNSU;
		$iCount     = 0;
		$last       = "";
		$arrayDocs  = [];
		

		while ($ultNSU <= $maxNSU) {
			$iCount++;
			if ($iCount >= $loopLimit) {
				break;
			}
			try {
				$resp 	= $configuracao->tools->sefazDistDFe($ultNSU);
				$dom 	= new \DOMDocument();
				$dom->loadXML($resp);
				$node 	= $dom->getElementsByTagName('retDistDFeInt')->item(0);
				$cStat 	= $node->getElementsByTagName('cStat')->item(0)->nodeValue;
				$xMotivo= $node->getElementsByTagName('xMotivo')->item(0)->nodeValue;
				$ultNSU = $node->getElementsByTagName('ultNSU')->item(0)->nodeValue;
				$maxNSU = $node->getElementsByTagName('maxNSU')->item(0)->nodeValue;
				$lote 	= $node->getElementsByTagName('loteDistDFeInt')->item(0);

				// Verifica se não há mais documentos (cStat 656)

				$obj 			= new stdClass;
				$obj->cnpj 		= $cnpj;
				$obj->ultnsu 	= $ultNSU;
				$obj->maxnsu 	= $maxNSU;
				Service::editar(objToArray($obj), "cnpj", "certificado_digital");

				if ($cStat = '137') { // Exemplo de múltiplos status
					break;
				}

				if (in_array($cStat, [ '656', '999'])) { // Exemplo de múltiplos status
					return $xMotivo;
				}
	
				if (empty($lote) || $last == $ultNSU) {
					continue;
				}
	
				$last = $ultNSU;
				$docs = $lote->getElementsByTagName('docZip');
	
				foreach ($docs as $doc) {
					$numnsu 	= $doc->getAttribute('NSU');
					$schema 	= $doc->getAttribute('schema');
					$content 	= gzdecode(base64_decode($doc->nodeValue));
					$xml 		= simplexml_load_string($content);					
	
					// Extrair o tipo do documento com base no schema
					$tipoDoc = self::identificarTipoDocumento($schema);	
					
					$chave = self::extrairChaveAcesso($xml);
					// Salva o XML na pasta com o nome gerado
					$atributosGenericos = self::extrairAtributosGenericos($xml);
					// Juntando os arrays com array_merge()
					$temp = array_merge([
						'cnpj'     		=> $cnpj,
						'chave'         => $chave,
						'nsu'           => $numnsu,
						'ultnsu'        => $ultNSU,
						'maxnsu'        => $maxNSU,
						'tipo'          => $tipoDoc,
						'fatura_salva'  => false,
						'sequencia_evento' => 0
					], $atributosGenericos); // Atributos genéricos têm prioridade
					
					// Salvar no banco de dados
					if($tipoDoc=="nfe" || $tipoDoc=="cte" || $tipoDoc=="mdfe" || $tipoDoc=="gnre" || $tipoDoc=="cce"){
						$nomePasta = PASTA_DOWNLOAD . $cnpj ."/" .$tipoDoc ."/";
						if (!is_dir($nomePasta)) {
							mkdir($nomePasta , 0777, true);
						}						
						$nomeArquivo = $nomePasta . "{$tipoDoc}_{$chave}_{$numnsu}.xml";
						file_put_contents($nomeArquivo, $content);
						Service::inserir($temp,"dfe");
					}else{
						$nomePasta = PASTA_DOWNLOAD . $cnpj ."/" .$tipoDoc ."/";
						if (!is_dir($nomePasta)) {
							mkdir($nomePasta , 0777, true);
						}
						$nomeArquivo = $nomePasta . "{$tipoDoc}_{$chave}_{$numnsu}.xml";
						file_put_contents($nomeArquivo, $content);
						Service::inserir($temp,"dfe_diverso");
					}
					
					$obj 			= new stdClass;
					$obj->cnpj 		= $cnpj;
					$obj->nsu 		= $numnsu;
					Service::editar(objToArray($obj), "cnpj", "certificado_digital");

					array_push($arrayDocs, $temp);
				}
				sleep(2);
			} catch (\Exception $e) {
				return $e->getMessage();
			}
		}
		return $arrayDocs;
	}
	
	
	public static function extrairChaveAcesso($xml) {
		$chave = null;
	
		// Obtém todos os namespaces do XML
		$namespaces = $xml->getNamespaces(true);
	
		// Registra o namespace principal (caso exista)
		foreach ($namespaces as $prefix => $ns) {
			if ($prefix === '') {
				$xml->registerXPathNamespace('doc', $ns);
			} else {
				$xml->registerXPathNamespace($prefix, $ns);
			}
		}
	
		// Tenta encontrar a chave em qualquer tag correspondente (NF-e, CT-e, MDFe, etc.)
		$result = $xml->xpath('//doc:chNFe | //chNFe | //doc:chCTe | //chCTe | //doc:chMDFe | //chMDFe');
		
		if (isset($result[0])) {
			$chave = (string)$result[0];
		}
	
		return $chave;
	}
	
	public static function extrairChaveAcesso2($xml) {
		$chave = null;
	
		// Obtém todos os namespaces do XML
		$namespaces = $xml->getNamespaces(true);
	
		// Registra o namespace principal (caso exista)
		foreach ($namespaces as $prefix => $ns) {
			if ($prefix === '') {
				$xml->registerXPathNamespace('nfe', $ns);
			} else {
				$xml->registerXPathNamespace($prefix, $ns);
			}
		}
	
		// Tenta encontrar a chave em qualquer tag chamada 'chNFe' usando XPath, independentemente do nível ou namespace
		$result = $xml->xpath('//nfe:chNFe | //chNFe');
		if (isset($result[0])) {
			$chave = (string)$result[0];
		}
	
		return $chave;
	}
	
	

	
	
	
	

	public static function buscarNsu($configuracao){
		$retorno = new stdClass();
		$retorno->tem_erro = false;
		$ultNSU = 0;
		try {
			// Faz a consulta na SEFAZ
			$resp = $configuracao->tools->sefazDistDFe($ultNSU);
			$dom = new \DOMDocument();
			$dom->loadXML($resp);
			$node = $dom->getElementsByTagName('retDistDFeInt')->item(0);				
			$resultado = (object) [
				'cStat'   	=> $node->getElementsByTagName('cStat')->item(0)->nodeValue ?? null,
				'xMotivo' 	=> $node->getElementsByTagName('xMotivo')->item(0)->nodeValue ?? null,
				'dhResp' 	=> $node->getElementsByTagName('dhResp')->item(0)->nodeValue ?? null,
				'ultNSU' 	=> $node->getElementsByTagName('ultNSU')->item(0)->nodeValue ?? null,
				'maxNSU' 	=> $node->getElementsByTagName('maxNSU')->item(0)->nodeValue ?? null,
				'lote' 		=> $node->getElementsByTagName('loteDistDFeInt')->item(0)
			];
			// Verifica erros na resposta da SEFAZ
			if ($resultado->cStat == '656') {
				throw new Exception($resultado->xMotivo);
			}

			$retorno->tem_erro 	= false;
			$retorno->resultado = $resultado;
		} catch (\Exception $e) {
			$retorno->tem_erro 	= true;
			$retorno->erro 		= $e->getMessage();
		}

		return $retorno;		
	}




























	public static function consultarDocumentosEmLotes($configuracao, $nsuInicial, $nsuFinal, $tamanhoLote = 100)
{
    $arrayDocs = [];  // Armazena os documentos encontrados
    $ultimoNSU = $nsuInicial;  // Começa do NSU especificado

    while ($ultimoNSU <= $nsuFinal) {
        try {
            // Limita o intervalo do lote (consulta no máximo $tamanhoLote por vez)
            $nsuLimite = min($ultimoNSU + $tamanhoLote - 1, $nsuFinal);

            // Realiza a consulta para o intervalo atual
            $resp = $configuracao->tools->sefazDistDFe($ultimoNSU);

            // Carrega a resposta XML
            $dom = new \DOMDocument();
            $dom->loadXML($resp);

            // Obtém o nó principal da resposta
            $node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
            if (!$node) {
                throw new Exception("Resposta inválida da SEFAZ.");
            }

            // Lê os valores de cStat e ultNSU da resposta
            $cStat = $node->getElementsByTagName('cStat')->item(0)->nodeValue;
            $ultNSU = $node->getElementsByTagName('ultNSU')->item(0)->nodeValue;

            // Verifica se não há mais documentos (cStat 656)
            if ($cStat == '656') {
                echo "Nenhum documento disponível para este lote.\n";
                break;  // Encerra o loop se não houver documentos
            }

            // Obtém o lote de documentos, se houver
            $lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);
            $docs = $lote ? $lote->getElementsByTagName('docZip') : [];

            // Itera sobre os documentos encontrados
            foreach ($docs as $doc) {
                // Decodifica e descompacta o conteúdo do documento
                $content = gzdecode(base64_decode($doc->nodeValue));
                $xml = simplexml_load_string($content);

                // Monta um array com as informações relevantes do documento
                $temp = [
                    'documento' => (string) ($xml->CNPJ ?? ''),
                    'data_emissao' => (string) ($xml->dhEmi ?? ''),
                    'valor' => (float) ($xml->vNF ?? 0.0),
                    'chave' => (string) ($xml->chNFe ?? ''),
                    'nsu' => $doc->getAttribute('NSU')
                ];

                // Adiciona o documento ao array de resultados
                array_push($arrayDocs, $temp);
            }

            // Atualiza o último NSU para continuar do próximo lote
            $ultimoNSU = $ultNSU + 1;

            // Pausa para evitar sobrecarga de requisições
            sleep(5);  // Ajuste conforme necessário
        } catch (\Exception $e) {
            echo "Erro: " . $e->getMessage();
            break;  // Interrompe o loop em caso de erro
        }
    }

    // Retorna os documentos encontrados
    return $arrayDocs;
}




	public static function consultar($nsuInicial, $configuracao)
{
    $arrayDocs = [];  // Array para armazenar os documentos encontrados
    $ultNSU = $nsuInicial;  // NSU inicial passado como parâmetro
    $maxNSU = $ultNSU;  // Inicializa o maxNSU

    do {
        try {
            // Realiza a consulta de DFe na SEFAZ para o NSU atual
            $resp = $configuracao->tools->sefazDistDFe($ultNSU);

            // Carrega a resposta XML
            $dom = new \DOMDocument();
            $dom->loadXML($resp);

            // Obtém o nó principal da resposta
            $node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
            if (!$node) {
                throw new Exception("Resposta inválida da SEFAZ.");
            }

            // Lê os valores de cStat, ultNSU e maxNSU da resposta
            $cStat = $node->getElementsByTagName('cStat')->item(0)->nodeValue;
            $ultNSU = $node->getElementsByTagName('ultNSU')->item(0)->nodeValue;
            $maxNSU = $node->getElementsByTagName('maxNSU')->item(0)->nodeValue;
			
            // Verifica se não há mais documentos (cStat 656)
            if ($cStat == '656') {
                echo "Nenhum documento disponível para este NSU.\n";
                break;  // Encerra o loop, pois não há mais documentos
            }

            // Obtém o lote de documentos, se existir
            $lote = $node->getElementsByTagName('loteDistDFeInt')->item(0);
            $docs = $lote ? $lote->getElementsByTagName('docZip') : [];

            // Itera sobre os documentos encontrados
            foreach ($docs as $doc) {
                // Decodifica e descompacta o conteúdo do documento
                $content = gzdecode(base64_decode($doc->nodeValue));
                $xml = simplexml_load_string($content);

                // Monta um array com as informações relevantes do documento
                $temp = [
                    'documento' => (string) ($xml->CNPJ ?? ''),
					'nome' => (string) ($xml->xNome ?? ''),
                    'data_emissao' => (string) ($xml->dhEmi ?? ''),
                    'valor' => (float) ($xml->vNF ?? 0.0),
                    'chave' => (string) ($xml->chNFe ?? ''),
					'num_prot' => (string) ($xml->nProt ?? ''),
                    'nsu' => $doc->getAttribute('NSU')					
                ];

                // Adiciona o documento ao array de resultados
                array_push($arrayDocs, $temp);
            }

            // Pausa para evitar sobrecarga de requisições
            sleep(2);
        } catch (\Exception $e) {
            echo "Erro: " . $e->getMessage();
            break;  // Interrompe o loop em caso de erro
        }
    } while ($ultNSU < $maxNSU);  // Continua até que o ultNSU seja igual ao maxNSU

    // Retorna o array de documentos encontrados
    return $arrayDocs;
}

	public static function novaConsulta($nsu, $configuracao)
	{
		$retorno = new stdClass();
		$retorno->tem_erro = false;
		$arrayDocs = [];
		$ultNSU = $nsu;
		$maxNSU = $ultNSU;
		$loopLimit = 10;
		$iCount = 0;
		$last = "";
	
		while ($ultNSU <= $maxNSU) {
			$iCount++;
			if ($iCount >= $loopLimit) {
				break;
			}
	
			try {
				// Faz a consulta na SEFAZ
				$resp = $configuracao->tools->sefazDistDFe($ultNSU);

				$dom = new \DOMDocument();
				$dom->loadXML($resp);
	
				$node = $dom->getElementsByTagName('retDistDFeInt')->item(0);
	
				if (!$node) {
					continue; // Ignora se não encontrar o nó esperado
				}
	
				$resultado = (object) [
					'tpAmb' => $node->getElementsByTagName('tpAmb')->item(0)->nodeValue ?? null,
					'verAplic' => $node->getElementsByTagName('verAplic')->item(0)->nodeValue ?? null,
					'cStat' => $node->getElementsByTagName('cStat')->item(0)->nodeValue ?? null,
					'xMotivo' => $node->getElementsByTagName('xMotivo')->item(0)->nodeValue ?? null,
					'dhResp' => $node->getElementsByTagName('dhResp')->item(0)->nodeValue ?? null,
					'ultNSU' => $node->getElementsByTagName('ultNSU')->item(0)->nodeValue ?? $ultNSU,
					'maxNSU' => $node->getElementsByTagName('maxNSU')->item(0)->nodeValue ?? $maxNSU,
					'lote' => $node->getElementsByTagName('loteDistDFeInt')->item(0),
				];

				// Verifica erros na resposta da SEFAZ
				if ($resultado->cStat == '656') {
					throw new Exception($resultado->xMotivo);
				}
	
				if ($last != $resultado->ultNSU) {
					$last = $resultado->ultNSU;
					$ultNSU = $resultado->ultNSU; // Atualiza o último NSU para a próxima iteração
					$maxNSU = $resultado->maxNSU;
	
					if (empty($resultado->lote)) {
						continue;
					}
	
					// Processa os documentos encontrados
					$docs = $resultado->lote->getElementsByTagName('docZip');
					foreach ($docs as $doc) {
						$numnsu = $doc->getAttribute('NSU');
						$schema = $doc->getAttribute('schema');
						$content = gzdecode(base64_decode($doc->nodeValue));
	
						// Verifica se o conteúdo foi extraído corretamente
						if ($content === false) {
							throw new Exception("Erro ao decodificar o conteúdo do documento NSU: $numnsu");
						}
	
						// Carrega o XML e verifica se foi bem-sucedido
						$xml = simplexml_load_string($content);
						if ($xml === false) {
							throw new Exception("Erro ao carregar o XML para o documento NSU: $numnsu");
						}
	
						// Verifica se os campos esperados existem no XML
						$temp = [
							'documento' => (string) ($xml->CNPJ ?? ''),
							'nome' => (string) ($xml->xNome ?? ''),
							'data_emissao' => (string) ($xml->dhEmi ?? ''),
							'valor' => (float) ($xml->vNF ?? 0.0),
							'num_prot' => (string) ($xml->nProt ?? ''),
							'chave' => (string) ($xml->chNFe ?? ''),
							'nsu' => $numnsu,
							'sequencia_evento' => 0,
						];
						
						array_push($arrayDocs, $temp);
					}
	
					sleep(2); // Evita sobrecarga de requisições
				}
			} catch (\Exception $e) {
				$retorno->tem_erro = true;
				$retorno->erro = $e->getMessage();
				break; // Interrompe em caso de erro
			}
		}
		$retorno->documentos = $arrayDocs;
		return $retorno;
	}
	




    

    
   

}
