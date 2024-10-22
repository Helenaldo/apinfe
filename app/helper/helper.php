<?php
function i($array){
    echo "<pre>";
    print_r($array);
    echo "</pre>";
    exit;
}

function tira_mascara($valor){
    return  preg_replace("/\D+/", "", $valor);
}

function objToArray($objeto){
    return is_array($objeto) ? $objeto : (array) $objeto;
}

function validaEmail($email){  
    $conta = "/[a-zA-Z0-9\._-]+@";
    $domino = "[a-zA-Z0-9\._-]+.";
    $extensao = "([a-zA-Z]{2,4})$/";
    $pattern = $conta.$domino.$extensao;    
    if (preg_match($pattern, $email))
        return true;
    else
        return false;
 }
 

 function converterDataHora($dataISO) {
    // Verifica se a data não está vazia
    if (empty($dataISO)) {
        return ['data' => "0000-00-00", 'hora' => "00:00:00"];
    }

    // Tenta criar o objeto DateTime; se falhar, a data é inválida
    try {
        $dateTime = new DateTime($dataISO);
    } catch (Exception $e) {
        return ['data' => "0000-00-00", 'hora' => "00:00:00"];
    }

    // Separa a data e a hora
    $data = $dateTime->format('Y-m-d'); // Formato da data: YYYY-MM-DD
    $hora = $dateTime->format('H:i:s'); // Formato da hora: HH:MM:SS

    return ['data' => $data, 'hora' => $hora];
}

function zeroEsquerda($str, $qtde){
    return str_pad($str, $qtde,'0',STR_PAD_LEFT);
}


function getFloat($valor, $simbolo = null, $casasDecimais = 2){
    if($valor){
        $valor = verificaValor($valor);
    if (isset($casasDecimais))
        return (float) number_format($valor, $casasDecimais,'.','');
    else
        return (float) $valor;
    }

}

function verificaValor($val, $excep = false){
    $val = preg_replace('/[^\d\.\,]+/', '', $val);
    // Inteiro
    if (preg_match('/^\d+$/', $val)) {
        $valor = (float) $val;
    } else
        // Float
        if (preg_match('/^\d+\.{1}\d+$/', $val)) {
            $valor = (float) $val;
        } else{
            // Vírgula como separador decimal
            if (preg_match('/^[\d\.]+\,{1}\d+$/', $val)) {
                $valor = (float) str_replace(',','.', str_replace('.', '', $val));
            } else {        // Formato inválido ou em branco
                if($excep)
                   // throw new \Exception('Moeda em formato inválido ou desconhecido.');
                    $valor = 0;
            }
        }
     return $valor;
}
function dataNfe($data){
    return substr($data,0,10);;
}
 
 function validaCPF($cpf){     
     // Extrai somente os números
     $cpf = preg_replace('/[^0-9]/is', '', $cpf);
     
     // Verifica se foi informado todos os digitos corretamente
     if (strlen($cpf) != 11) {
         return false;
     }
     
     // Verifica se foi informada uma sequência de digitos repetidos. Ex: 111.111.111-11
     if (preg_match('/(\d)\1{10}/', $cpf)) {
         return false;
     }
     
     // Faz o calculo para validar o CPF
     for ($t = 9; $t < 11; $t ++) {
         for ($d = 0, $c = 0; $c < $t; $c ++) {
             $d += $cpf[$c] * (($t + 1) - $c);
         }
         
         $d = ((10 * $d) % 11) % 10;
         if ($cpf[$c] != $d) {
             return false;
         }
     }
     return true;
 }
 
 function validaCNPJ($cnpj) {
     $cnpj = preg_replace('/[^0-9]/', '', (string) $cnpj);
     
     // Valida tamanho
     if (strlen($cnpj) != 14)
         return false;
         
         // Verifica se todos os digitos são iguais
         if (preg_match('/(\d)\1{13}/', $cnpj))
             return false;
             
             // Valida primeiro dígito verificador
             for ($i = 0, $j = 5, $soma = 0; $i < 12; $i ++) {
                 $soma += $cnpj[$i] * $j;
                 $j = ($j == 2) ? 9 : $j - 1;
             }
             
             $resto = $soma % 11;
             
             if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto))
                 return false;
                 
                 // Valida segundo dígito verificador
                 for ($i = 0, $j = 6, $soma = 0; $i < 13; $i ++) {
                     $soma += $cnpj[$i] * $j;
                     $j = ($j == 2) ? 9 : $j - 1;
                 }
                 
                 $resto = $soma % 11;
                 
                 return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
 }

 function formataNumero($number, $dec = 2){
    return number_format((float) $number, $dec, ".", "");
}

function tiraAcento($str){
    $comAcentos = array('à', 'á', 'â', 'ã', 'ä', 'å', 'ç', 'è', 'é', 'ê', 'ë', 'ì', 'í', 'î', 'ï', 'ñ', 'ò', 'ó', 'ô', 'õ', 'ö', 'ù', 'ü', 'ú', 'ÿ', 'À', 'Á', 'Â', 'Ã', 'Ä', 'Å', 'Ç', 'È', 'É', 'Ê', 'Ë', 'Ì', 'Í', 'Î', 'Ï', 'Ñ', 'Ò', 'Ó', 'Ô', 'Õ', 'Ö', 'O', 'Ù', 'Ü', 'Ú');
    $semAcentos = array('a', 'a', 'a', 'a', 'a', 'a', 'c', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'n', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'y', 'A', 'A', 'A', 'A', 'A', 'A', 'C', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'N', 'O', 'O', 'O', 'O', 'O', '0', 'U', 'U', 'U');
    return str_replace($comAcentos, $semAcentos, $str);
}


 function config($key, $default = null) {
    static $configurations = [];

    $segments = explode('.', $key);
    $file = array_shift($segments);

    if (!isset($configurations[$file])) {
        $path ="./config/{$file}.php";
        if (file_exists($path)) {
            $configurations[$file] = require $path;
        } else {
            // Arquivo de configuração não encontrado
            return $default;
        }
    }

    $value = $configurations[$file];
    foreach ($segments as $segment) {
        if (isset($value[$segment])) {
            $value = $value[$segment];
        } else {
            // Chave não encontrada
            return $default;
        }
    }

    return $value;
}
 
//função limata caracteres
function limita_caracteres($texto, $limite, $quebra = true){
    $tamanho = strlen($texto);
    if($tamanho <= $limite){ //Verifica se o tamanho do texto é menor ou igual ao limite
        $novo_texto = $texto;
    }else{ // Se o tamanho do texto for maior que o limite
        if($quebra == true){ // Verifica a opção de quebrar o texto
            $novo_texto = trim(substr($texto, 0, $limite))."...";
        }else{ // Se não, corta $texto na última palavra antes do limite
            $ultimo_espaco = strrpos(substr($texto, 0, $limite), " "); // Localiza o útlimo espaço antes de $limite
            $novo_texto = trim(substr($texto, 0, $ultimo_espaco))."..."; // Corta o $texto até a posição localizada
        }
    }
    return $novo_texto; // Retorna o valor formatado
}


 ///
 function upload($arq, $config_upload){
     set_time_limit(0);
     $nome_arquivo 		 = $_FILES[$arq]["name"];
     $tamanho_arquivo 	 = $_FILES[$arq]["size"];
     $arquivo_temporario = $_FILES[$arq]["tmp_name"];
     $erro               = 0;
     $msg                = "";
     $retorno            = array();
     if(!empty($nome_arquivo)){
         $ext        = strrchr($nome_arquivo, ".");
         $nome_final = ($config_upload["renomeia"]) ? md5(time()) . $ext:  $nome_arquivo;
         $caminho    = $config_upload["caminho_absoluto"] .$nome_final;
         
         
         if (($config_upload["verifica_tamanho"]) && ($tamanho_arquivo > $config_upload["tamanho"])){
             $msg ="O arquivo é maior que o permitido" ;
             $erro = -1;
         }
         
         if(($config_upload["verifica_extensao"]) && (!in_array($ext,$config_upload["extensoes"]))){
             $msg ="O arquivo não é permitido para upload";
             $erro = -1;
         }
         
         if($erro !=-1){
             if(move_uploaded_file($arquivo_temporario, $caminho)){
                 $msg    =  "Arquivo enviado com sucesso";
                 $erro   =  0;
             }else{
                 $msg    = "erro ao fazer o upload";
                 $erro   = -1;
             }
         }
         
     }else{
         $msg = "Arquivo vazio";
         $erro = -1;
     }
     $retorno = (object) array("erro" => $erro, "msg"=> $msg, "nome"=>$nome_final);
     return $retorno;
 }
 

 
 