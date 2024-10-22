<?php
namespace app\models\service;

use app\models\model\Adicional;
use app\models\model\Cartao;
use app\models\model\Configuracao;
use app\models\model\Destinatario;
use app\models\model\Duplicata;
use app\models\model\Emitente;
use app\models\model\Fatura;
use app\models\model\Ide;
use app\models\model\Pagamento;
use app\models\model\Produto;
use app\models\model\Reboque;
use app\models\model\RetencaoTransporte;
use app\models\model\Total;
use app\models\model\Transportadora;
use app\models\model\Veiculo;
use app\models\model\Volume;
use NFePHP\Common\Certificate;
use NFePHP\NFe\Tools;
use stdClass;

class ValidaDadosNfeService{

    public static function valida($dados){
        $retorno = new stdClass;
        $notafiscal = new stdClass;
        try{
            if(!isset($dados->ide)){
                $retorno->titulo =  "Erro ao ler objeto";
                throw new \Exception("É Obrigatório o envio do node Ide para emissão da NFE");
            }

            if(!isset($dados->emitente)){
                $retorno->titulo =  "Erro ao ler objeto";
                throw new \Exception("É Obrigatório o envio do node Emitente para emissão da NFE");
            }

            if(!isset($dados->destinatario)){
                $retorno->titulo =  "Erro ao ler objeto";
                throw new \Exception("É Obrigatório o envio do node Destinatario para emissão da NFE");
            }

            if(!isset($dados->itens)){
                $retorno->titulo =  "Erro ao ler objeto";
                throw new \Exception("É Obrigatório o envio do node Itens para emissão da NFE");
            }

            if(!isset($dados->pagamentos)){
                $retorno->titulo =  "Erro ao ler objeto";
                throw new \Exception("É Obrigatório o envio do node pagamentos para emissão da NFE");
            }    
            
            //NOde IDE
            $ide = self::validaIde($dados->ide, $dados->emitente, $dados->destinatario);
            $notafiscal->ide = $ide;

             //NOde Emitente
             $emitente = self::validaEmitente($dados->emitente);
             $notafiscal->emitente = $emitente;
 
             //NOde Destinatároi
             $destinatario = self::validaDestinatario($dados->destinatario);
             $notafiscal->destinatario = $destinatario;

             $total      = new Total();
             foreach($dados->itens as $item){    
                $it = new stdClass;         
                if(!isset($item->produto)){
                    $retorno->titulo =  "Erro ao ler objeto";
                    throw new \Exception("É Obrigatório o envio do node Produto   para emissão da NFE");
                }

                if(!isset($item->icms)){
                    throw new \Exception('É obrigatório o envio do node icms para emissão da nota');
                }

                $item->icms->ipi =  0;
                $ipi = null;
                if(isset($item->ipi)){
                    $ipi = ValidarImpostoService::validarIpi($item->ipi);
                    if($ipi->vIPI){
                        $item->icms->ipi =  $ipi->vIPI;
                    }
                }
                if($ipi){
                    $it->ipi     = $ipi;
                }
                
                if(!isset($item->pis)){
                    throw new \Exception('É obrigatório o envio do node pis para emissão da nota');
                }
                
                if(!isset($item->cofins)){
                    throw new \Exception('É obrigatório o envio do node cofins para emissão da nota');
                }  
                
                $it = new stdClass;
                $it->produto = self::validaProduto($item->produto);

                $it->icms    = ValidarImpostoService::validarIcms($item->icms);
                $it->pis     = ValidarImpostoService::validarPis($item->pis);
                $it->cofins  = ValidarImpostoService::validarCofins($item->cofins);



                
                $vBC = $it->icms->vBC ;
                $vICMS = $it->icms->vICMS ;
                if($it->icms->CST=="102" || $it->icms->CST=="103" || $it->icms->CST=="300" || $it->icms->CST=="400"  ){
                    $vBC = 0;
                    $vICMS = 0;
                }
                 //Totais
                 $total->vBC			  += $vBC ;
                 $total->vICMS            += $vICMS ;
                 $total->vICMSDeson       += $it->icms->vICMSDeson ;
                 $total->vBCST            += $it->icms->vBCST ;
                 $total->vProd            += $it->produto->vProd ;
                 $total->vFrete           += $it->produto->vFrete ;
                 $total->vSeg             += $it->produto->vSeg ;
                 $total->vDesc            += $it->produto->vDesc ;
                 $total->vII              +=  null ;
                 $total->vIPI             += $it->ipi->vIPI ?? 0;
                 $total->vPIS             += $it->pis->vPIS ;
                 $total->vCOFINS          += $it->cofins->vCOFINS;
                 $total->vOutro           += $it->produto->vOutro ;
                 $total->vFCP             += $it->icms->vFCP ;
                 $total->vFCPST           += $it->icms->vFCPST ;
                 $total->vFCPSTRet        += $it->icms->vFCPSTRet ;
                 $total->vNF              += $it->produto->vProd + $it->produto->vFrete + $it->produto->vSeg + $it->produto->vOutro + $it->produto->vOutro +
                                                 $total->vIPI  +    $it->icms->vFCPST  - $it->produto->vDesc - $it->icms->vICMSDeson;
                $notafiscal->itens[] = $it;

            }

            //Nó Pagamento
            $pagamentos   = self::validarPagamentos($dados->pagamentos);
            $notafiscal->pagamentos = $pagamentos;

            //Nó Transportadora
            if(isset($dados->transporte)){
                $transporte   = self::validarTransporte($dados->transporte);
                $notafiscal->transporte = $transporte;
            }

            //Nó Cobranca
            if(isset($dados->cobranca)){
                $cobranca   = self::validarCobranca($dados->cobranca);
                $notafiscal->cobranca = $cobranca;
            }   
            
            //Nó Adicionais
            if(isset($dados->adicionais)){
                $adicionais   = self::validarAdicionais($dados->adicionais);
                $notafiscal->adicionais = $adicionais;
            }

            //Verifica se o certificado digital existe;
            $certificado = Service::get("certificado_digital","cnpj", $emitente->CNPJ );
            if(!$certificado){
                $retorno->titulo    = "Erro ao ler Cerificado Digital";
                throw new \Exception('Nenhum certificado digital foi encontrado para este CNPJ');
            }
            $notafiscal->certificado_digital = $certificado;

            //Popoular Configuracao
            $configuracao           = self::validarConfiguracao($emitente, $ide, $certificado);
            $notafiscal->configuracao = $configuracao;

             $retorno->tem_erro = false;
            $retorno->erro = "";
            $retorno->notafiscal = $notafiscal;
            return $retorno;
        } catch (\Throwable $th) {
            $retorno->tem_erro = true;
            $retorno->erro = $th->getMessage();
            $retorno->notafiscal = null;
            return $retorno;
        }
        return $retorno;
    }

    public static function validaIde($dados, $emitente, $destinatario){

        $dados->cUF             = ConstanteService::getUf($emitente->UF);
        $dados->cMunFG          = $emitente->cMun;

        if(!isset($dados->nNF)  || is_null($dados->nNF)) {
            throw new \Exception('O Campo nNF é Obrigatório');
        }

        if(!isset($dados->natOp)  || is_null($dados->natOp)) {
            throw new \Exception('O Campo natOp é Obrigatório');
        }

        if(!isset($dados->mod)  || is_null($dados->mod)) {
            throw new \Exception('O Campo mod é Obrigatório');
        }

        if(!isset($dados->serie)   || is_null($dados->serie)) {
            throw new \Exception('O Campo serie é Obrigatório');
        }

        if(!isset($dados->dhEmi)   || is_null($dados->dhEmi)) {
            throw new \Exception('O Campo dhEmi é Obrigatório');
        }

        if(!isset($dados->tpImp)   || is_null($dados->tpImp)) {
            throw new \Exception('O Campo tpImp é Obrigatório');
        }

        if(!isset($dados->tpEmis)   || is_null($dados->tpEmis)) {
            throw new \Exception('O Campo tpEmis é Obrigatório');
        }

        if(!isset($dados->tpAmb)   || is_null($dados->tpAmb)) {
            throw new \Exception('O Campo tpAmb é Obrigatório');
        }
        if(!isset($dados->finNFe)   || is_null($dados->finNFe)) {
            throw new \Exception('O Campo finNFe é Obrigatório');
        }
        if(!isset($dados->indFinal)   || is_null($dados->indFinal)) {
            throw new \Exception('O Campo indFinal é Obrigatório');
        }

        if(!isset($dados->procEmi)   || is_null($dados->procEmi)) {
            throw new \Exception('O Campo procEmi é Obrigatório');
        }
        if(!isset($dados->verProc)   || is_null($dados->verProc)) {
            throw new \Exception('O Campo verProc é Obrigatório');
        }

        if(!isset($dados->modFrete)  || is_null($dados->modFrete)) {
            throw new \Exception('O Campo modFrete do node Ide é Obrigatório');
        }

        if(!isset($emitente->UF) ||  is_null($emitente->UF)) {
            throw new \Exception('O Campo UF do node Emitente é Obrigatório');
        }
        if(!isset($destinatario->UF) || is_null($destinatario->UF)) {
            throw new \Exception('O Campo UF do node Destinatario é Obrigatório');
        }

        if($emitente->UF !="EX"){
            if($emitente->UF == $destinatario->UF){
                $dados->idDest = config("constanteNota.idDest.INTERNA");
            }else{
                $dados->idDest = config("constanteNota.idDest.INTERESTADUAL");
            }
        }else{
            $dados->idDest = config("constanteNota.idDest.EXTERIOR");
        }
   
        $ide = new Ide();
        $ide->setarDados($dados);
        return $ide;

    }

    public static function validaEmitente($dados){

        if(!isset($dados->CNPJ) ||  is_null($dados->CNPJ)) {
            throw new \Exception('O Campo CNPJ do node Emitente é Obrigatório');
        }
        if(!isset($dados->xNome) ||  is_null($dados->xNome)) {
            throw new \Exception('O Campo xNome  do node Emitente é Obrigatório');
        }
        if(!isset($dados->xLgr) ||  is_null($dados->xLgr)) {
            throw new \Exception('O Campo xLgr do node Emitente é Obrigatório');
        }
        if(!isset($dados->nro) ||  is_null($dados->nro)) {
            throw new \Exception('O Campo nro do node Emitente é Obrigatório');
        }
        if(!isset($dados->xBairro) ||  is_null($dados->xBairro)) {
            throw new \Exception('O Campo xBairro do node Emitente é Obrigatório');
        }
        if(!isset($dados->cMun) ||  is_null($dados->cMun)) {
            throw new \Exception('O Campo cMun do node Emitente é Obrigatório');
        }
        if(!isset($dados->xMun) ||  is_null($dados->xMun)) {
            throw new \Exception('O Campo xMun do node Emitente é Obrigatório');
        }
        if(!isset($dados->UF) ||  is_null($dados->UF)) {
            throw new \Exception('O Campo UF do node Emitente é Obrigatório');
        }
        if(!isset($dados->CEP) ||  is_null($dados->CEP)) {
            throw new \Exception('O Campo CEP do node Emitente é Obrigatório');
        }
        if(!isset($dados->IE) ||  is_null($dados->IE)) {
            throw new \Exception('O Campo IE do node Emitente é Obrigatório');
        }
        if(!isset($dados->CRT) ||  is_null($dados->CRT)) {
            throw new \Exception('O Campo CRT do node Emitente é Obrigatório');
        }

        $emitente = new Emitente();
        $emitente->setarDados($dados);
        return $emitente;
    }

    public static function validaDestinatario($dados){

        if(!isset($dados->xNome) || is_null($dados->xNome)) {
            throw new \Exception('O Campo xNome do node Destinatário é Obrigatório');
        }
        if(!isset($dados->xLgr) ||  is_null($dados->xLgr)) {
            throw new \Exception('O Campo xLgr do node Destinatário  é Obrigatório');
        }
        if(!isset($dados->nro) ||  is_null($dados->nro)) {
            throw new \Exception('O Campo nro do node Destinatário  é Obrigatório');
        }
        if(!isset($dados->xBairro) ||  is_null($dados->xBairro)) {
            throw new \Exception('O Campo xBairro é Obrigatório');
        }
        if(!isset($dados->cMun) ||  is_null($dados->cMun)) {
            throw new \Exception('O Campo cMun do node Destinatário  é Obrigatório');
        }
        if(!isset($dados->xMun) ||  is_null($dados->xMun)) {
            throw new \Exception('O Campo xMun do node Destinatário  é Obrigatório');
        }
        if(!isset($dados->UF) ||  is_null($dados->UF)) {
            throw new \Exception('O Campo UF do node Destinatário  é Obrigatório');
        }
        if(!isset($dados->CEP) ||  is_null($dados->CEP)) {
            throw new \Exception('O Campo CEP do node Destinatário  é Obrigatório');
        }

        if(!isset($dados->CPF_CNPJ) ||  is_null($dados->CPF_CNPJ)) {
            throw new \Exception('O Campo CPF_CNPJ do node Destinatário  é Obrigatório');
        }

        if(!isset($dados->indIEDest) ||  is_null($dados->indIEDest)) {
            throw new \Exception('O Campo indIEDest do node Destinatário  é Obrigatório');
        }

        if($dados->indIEDest!=9){
            if(!isset($dados->IE) ||  is_null($dados->IE)) {
                throw new \Exception('A Incrição Estadual é obrigatória para Contribuintes ICMS');
            }
        }
        $cnpj  = tira_mascara($dados->CPF_CNPJ);
        if(strlen($cnpj) == 14){
            $dados->CNPJ = $cnpj;
            $dados->CPF  = null;

        }else{
            $dados->CPF = $cnpj;
            $dados->CNPJ  = null;
        }

        $destinatario = new Destinatario();
        $destinatario->setarDados($dados);
        return $destinatario;
    }

    public static function validaProduto($dados){
        if(!isset($dados->cProd) ||  is_null($dados->cProd)) {
            throw new \Exception('O Campo cProd do node Produto é Obrigatório');
        }
        if(!isset($dados->xProd) ||  is_null($dados->xProd)) {
            throw new \Exception('O Campo xProd do node Produto é Obrigatório');
        }
        if(!isset($dados->NCM) ||  is_null($dados->NCM)) {
            throw new \Exception('O Campo NCM do node Produto é Obrigatório');
        }
        if(!isset($dados->CFOP) ||  is_null($dados->CFOP)) {
            throw new \Exception('O Campo CFOP do node Produto é Obrigatório');
        }
        if(!isset($dados->uCom) ||  is_null($dados->uCom)) {
            throw new \Exception('O Campo uCom do node Produto é Obrigatório');
        }
        if(!isset($dados->qCom) ||  is_null($dados->qCom)) {
            throw new \Exception('O Campo qCom do node Produto é Obrigatório');
        }
        if(!isset($dados->vUnCom) ||  is_null($dados->vUnCom)) {
            throw new \Exception('O Campo vUnCom do node Produto é Obrigatório');
        }
        if(!isset($dados->vProd) ||  is_null($dados->vProd)) {
            throw new \Exception('O Campo vProd do node Produto é Obrigatório');
        }
        if(!isset($dados->uTrib) ||  is_null($dados->uTrib)) {
            throw new \Exception('O Campo uTrib do node Produto é Obrigatório');
        }

        if(!isset($dados->qTrib) ||  is_null($dados->qTrib)) {
            throw new \Exception('O Campo qTrib do node Produto é Obrigatório');
        }
        if(!isset($dados->vUnTrib) ||  is_null($dados->vUnTrib)) {
            throw new \Exception('O Campo vUnTrib do node Produto é Obrigatório');
        }

        if(!isset($dados->indTot) ||  is_null($dados->indTot)) {
            throw new \Exception('O Campo indTot do node Produto é Obrigatório');
        }

        $produto = new Produto();
        $produto->setarDados($dados);
        return $produto;
    }

    public static function validarPagamentos($array){
        if(count($array) <=0) {
            throw new \Exception('É Obrigatório ter pelo menos um pagamento');
        }
        $pagamentos = array();
        foreach($array as $pag){
            $detalhe = $pag->detalhe ?? null;
            if(!$detalhe){
                throw new \Exception('É obrigatório informar os detalhes do pagamento');
            }
            if(!isset($detalhe->tPag) ||  is_null($detalhe->tPag)) {
                throw new \Exception('O Campo tPag do Node Pagamento é Obrigatório');
            }
            if(!isset($detalhe->vPag) ||  is_null($detalhe->vPag)) {
                throw new \Exception('O Campo vPag do Node Pagamento é Obrigatório');
            }

            $pagamento = new Pagamento();
            $pagamento->setarDados($detalhe);

            $card = $pag->cartao ?? null;
            $cartao = null;
            if($card){
                if(!isset($card->tpIntegra) ||  is_null($card->tpIntegra)) {
                    throw new \Exception('O Campo tpIntegra do Node Pagamento é Obrigatório');
                }
                $cartao = new Cartao();
                $cartao->setarDados($card);
            }

            $pagamentos[] = array(
                "pagamento" => $pagamento,
                "cartao"    => $cartao
            );

        }
        return $pagamentos;
    }

    public static function validarTransporte($dados_transporte){
        $transporte = new stdClass;
        $transporte->transportadora = null;
        $transporte->retencao       = null;
        $transporte->veiculo        = null;
        $transporte->reboque        = null;
        $transporte->volume         = null;

        if(isset($dados_transporte->transportadora)){
            $transportadora = new Transportadora();
            $transportadora->setarDados($dados_transporte->transportadora);
            $transporte->transportadora = $transportadora;
        }

        if(isset($dados_transporte->retencao)){
            $dados      = (object) $dados_transporte->retencao;

            if(!isset($dados->vServ) ||  is_null($dados->vServ)) {
                throw new \Exception('O Campo vServ do node Retenção Transporte é Obrigatório');
            }
            if(!isset($dados->vBCRet) ||  is_null($dados->vBCRet)) {
                throw new \Exception('O Campo vBCRet do node Retenção Transporte é Obrigatório');
            }
            if(!isset($dados->pICMSRet) ||  is_null($dados->pICMSRet)) {
                throw new \Exception('O Campo pICMSRet do node Retenção Transporte é Obrigatório');
            }
            if(!isset($dados->vICMSRet) ||  is_null($dados->vICMSRet)) {
                throw new \Exception('O Campo vICMSRet do node Retenção Transporte é Obrigatório');
            }
            if(!isset($dados->CFOP) || is_null($dados->CFOP)) {
                throw new \Exception('O Campo CFOP do node Retenção Transporte é Obrigatório');
            }
            if(!isset($dados->cMunFG) ||  is_null($dados->cMunFG)) {
                throw new \Exception('O Campo cMunFG do node Retenção Transporte é Obrigatório');
            }

            $retencao = new RetencaoTransporte();
            $retencao->setarDados($dados_transporte->retencao);
            $transporte->retencao = $retencao;
        }
        if(isset($dados_transporte->veiculo)){
            $dados      = (object) $dados_transporte->veiculo;
            if(!isset($dados->placa) ||  is_null($dados->placa)) {
                throw new \Exception('O Campo placa do Node Veículo é Obrigatório');
            }

            if(!isset($dados->UF) ||  is_null($dados->UF)) {
                throw new \Exception('O Campo UF do Node Veículo é Obrigatório');
            }

            $veiculo    = new Veiculo();
            $veiculo->setarDados($dados_transporte->veiculo);
            $transporte->veiculo = $veiculo;
        }

        //Dados do Reboque
        if(isset($dados_transporte->reboque)){
            $dados      = (object) $dados_transporte->reboque;
            if(!isset($dados->placa) ||  is_null($dados->placa)) {
                throw new \Exception('O Campo placa do Node Reboque é Obrigatório');
            }

            if(!isset($dados->UF) ||  is_null($dados->UF)) {
                throw new \Exception('O Campo UF do Node Reboque é Obrigatório');
            }

            $reboque    = new Reboque();
            $reboque->setarDados($dados_transporte->reboque);
            $transporte->reboque = $reboque;
        }

        //Dados da Volume
        if(isset($dados_transporte->volume)){
            $volume = new Volume();
            $volume->setarDados($dados_transporte->volume);
            $transporte->volume = $volume;
        }
        return $transporte;

    }

    public static function validarCobranca($dados_cobranca){
        $cobranca = new stdClass();
        $cobranca->fatura = null;
        $cobranca->duplicatas = array();

        //Dados da fatura
        if(isset($dados_cobranca->fatura)){
            $fatura = new Fatura();
            $fatura->setarDados($dados_cobranca->fatura);
            $cobranca->fatura = $fatura;
        }

        //Dados da Duplicata
        if(isset($dados_cobranca->duplicatas)){
            if(count($dados_cobranca->duplicatas) <=0) {
                throw new \Exception('É Obrigatório ter pelo menos uma duplicata');
            }
            $i = 1;
            foreach($dados_cobranca->duplicatas as $dup){
                if(!isset($dup->dVenc) ||  is_null($dup->dVenc)) {
                    throw new \Exception('O Campo dVenc do Node Duplicata é Obrigatório');
                }
                if(!isset($dup->vDup) ||  is_null($dup->vDup)) {
                    throw new \Exception('O Campo vDup do Node Duplicata é Obrigatório');
                }
                $dup->nDup = zeroEsquerda($i++,3);
                $duplicata = new Duplicata();
                $duplicata->setarDados($dup);
                $cobranca->duplicatas[] = $duplicata;
            }
        }
        return $cobranca;
    }

    public static function validarAdicionais($dados){        
        $adicionais = new Adicional();
        $adicionais->setarDados($dados);
        return $adicionais;
    }

    public static function validarConfiguracao($emitente, $ide, $certificado){
        $arr = [
            "atualizacao" => date('Y-m-d h:i:s'),
            "tpAmb"       => intVal($ide->tpAmb),
            "razaosocial" => $emitente->xNome,
            "cnpj"        => $emitente->CNPJ,
            "siglaUF"     => $emitente->UF,
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
        $objeto                     = new Configuracao();
        $configJson                 = json_encode($arr);
        $objeto->cnpj               = $emitente->CNPJ;
        $objeto->tpAmb              = $ide->tpAmb;
        $objeto->tools              = new Tools($configJson, Certificate::readPfx($certificado->arquivo_binario, $certificado->senha));
        $objeto->pastaAmbiente      = ($ide->tpAmb == "1") ? "producao" : "homologacao";
        $objeto->pastaEmpresa       = $emitente->CNPJ;
        $objeto->tools->model($ide->mod);
        return $objeto;
    }




}
