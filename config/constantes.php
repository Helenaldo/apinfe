<?php
return [
    'status' => [
        'ATIVO'             => 1, // Utilizada para indicar que um recurso/modelo está ATIVO
        'DIGITACAO'         => 2, // Utilizada para indicar que um recurso/modelo Está em Digitacao
        'CONCRETIZADO'      => 3, // Utilizada para indicar que um recurso/modelo é novo
        'CANCELADO'         => 4, // Utilizada para indicar que um recurso/modelo é novo
        'DELETADO'          => 5, // Indica que algo foi cancelado automaticamente ou via administrativa
        'PENDENTE'          => 6, // Utilizada para indicar que um recurso/modelo foi DELETADO e não deve ser utilizado
        'ABERTO'            => 7,// Indica que algo está pendente
        'PARCIALMENTE_PAGO' => 8,// Utilizada quando ser quer setar um elemento como obrigatório para o sistema (indeletável)
        'PAGO'              => 9,
        'ATRASADO'          => 10,// Utilizada quando ser quer setar um elemento como obrigatório para o sistema (indeletável)
        'VENCIDO'           => 11,
        'ERRO_AO_GERAR_XML' => 12,
        'ERRO_AO_ASSINAR_XML' => 13,
        'ERRO_AO_ENVIAR_NFE'=> 14,
        'DENEGADO'          => 15,
        'REJEITADO'         => 16,
        'AUTORIZADO'        => 17, // Utilizada para indicar que um recurso/modelo está INATIVO
        'FINALIZADO'        => 18, // Indica que algo encerrou seu fluxo natural
        'ENVIADO'           => 19, // Quando couber o status ENVIADO para um recurso
        'INDISPONIVEL'      => 20,// Quando couber o status reservado para uma acomodação
        'OCUPADO'           => 21,// Quando couber o status reservado para uma acomodação
        'DISPONIVEL'        => 22,// Quando a acomodação está disponível
        'OBRIGATORIO'       => 23,// Utilizada quando ser quer setar um elemento como obrigatório para o sistema (indeletável)
        'FECHADO'           => 24,// Utilizada quando ser quer setar um elemento como obrigatório para o sistema (indeletável)
        'RECUSADO'          => 25,
        'EM_TRANSITO'       => 26,
        'ENTREGUE'          => 27,
        'EM_DIAS'           => 28,
        'BLOQUEADO'         => 29,
        'LIBERADO'          => 30,
        'DEMO'              => 31,
        'DEMO_VENCIDO'      => 32,
        'NAO_ASSINANTE'     => 33,
        'PROSPECTO'         => 34,
        'NOVO'              => 35,
        'INUTILIZADO'       => 36,
        'EM_PROCESSAMENTO'  => 37,
        'AGUARDANDO_FORNECEDOR'             => 38,
        'PRONTO_PARA_COTAR'                 => 39,
        'COTADO'                            => 40,
        'AGUARDANDO_COTACAO'                => 41,
        'AGUARDANDO_APROVACAO'              => 42,
        'APROVADO'                          => 43,
        'NAO_COMERCIALIZA'                  => 44,
        'NAO_ATENDE'                        => 45,
        'NO_ESTOQUE'                        => 46,
        'AGUARDANDO_LIBERACAO_PARA_INICIO'  => 47,
        'AGUARDANDO_COMPRA_DE_INSUMOS_FALTANTES'  => 48,
        'LIBERADO_PARA_INICIAR'             => 49,
        'INICIADO'                          => 50,
        'PROCESSO_FINALIZADO'               => 51,
        'PAUSADO'                           => 52,
        'EM_ESPERA'                         => 53,
        'EM_PRODUCAO'                       => 54,
        'PRONTO_PARA_FATURAR'               => 55,
        'FATURADO'                          => 56,
        'EM_COTACAO_DE_PRECO'               => 57,
        'EM_ORDEM_COMPRA'                   => 58,

    ],


    'tipo_documento' => [
        'VENDA'      => 1,
        'COMPRA'     => 2,
        'DESPESA'    => 3,
        'FATURA'     => 4,
        'AVULSO'     => 5,
        'CONTA_PAGAR'     => 6,
        'CONTA_RECEBER'     => 7,
    ],

    'dia_semana' => [
        'DOMINGO'   => 1,
        'SEGUNDA'   => 2,
        'TERCA'     => 3,
        'QUARTA'    => 4,
        'QUINTA'    => 5,
        'SEXTA'     => 6,
        'SABADO'    => 7,
    ],

    'forma_pagto'   => [
        'DINHEIRO'              => 1,
        'CHEQUE'                => 2,
        'CARTAO_CREDITO'        => 3,
        'CARTAO_DEBITO'         => 4,
        'CREDITO_LOJA'          => 5,
        'VALE_ALIMENTACAO'      => 10,
        'VALE_REFEICAO'         => 11,
        'VALE_PRESENTE'         => 12,
        'VALE_COMBUSTIVEL'      => 13,
        'DUPLICATA_MERCANTIL'   => 14,
        'BOLETO_BANCARIO'       => 15,
        'DEPOSITO_BANCARIO'     => 16,
        'PIX'                   => 17,
        'PROGRAMA_FIDELIDADE'   => 18,
        'SEMP_PAGAMENTO'        => 90,
        'OUTROS'                => 99,
    ],


    'pagamento'   => [
        '01' => 'Dinheiro',
        '02' => 'Cheque',
        '03' => 'Cartão de Crédito',
        '04' => 'Cartão de Débito',
        '05' => 'Crédito Loja',
        '10' => 'Vale Alimentação',
        '11' => 'Vale Refeição',
        '12' => 'Vale Presente',
        '13' => 'Vale Combustível',
        '14' => 'Duplicata Mercantil',
        '15' => 'Boleto Bancário',
        '16' => 'Depósito Bancário',
        '17' => 'PIX',
        '18' => 'Programa Fidelidade',
        '90' => 'Sem pagamento',
        '99' => 'Outros',
    ],

    'tipo_operacao' => [
        'CREDITO' => 1,
        'DEBITO' => 2
    ],


    'tipo_movimento' => [
        'ENTRADA_INICIO_ESTOQUE'        => '1',
        'ENTRADA_AVULSA'                => '2',
        'ENTRADA_AJUSTE_BALANCO'        => '3',
        'ENTRADA_COMPRA_MANUAL'         => '4',
        'ENTRADA_IMPORTACAO_XML'        => '5',
        'ENTRADA_TRANSFERENCIA_ESTOQUE' => '6',
        'ENTRADA_ORDEM_PRODUCAO'        => '7',
        'ENTRADA_CANCELAMENTO_VENDA'    => '8',

        'SAIDA_AVULSA'                  => '9',
        'SAIDA_VENDA_PRODUTO'           => '10',
        'SAIDA_AJUSTE_BALANCO'          => '11',
        'SAIDA_ORDEM_PRODUCAO'          => '12',
        'SAIDA_CONSUMO_INTERNO'         => '13',
        'SAIDA_TRANSFERENCIA_ESTOQUE'   => '14',
        'SAIDA_CANCELAMENTO_COMPRA'     => '15',
    ],


];
