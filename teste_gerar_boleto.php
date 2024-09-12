<?php

  require 'v_ambiente/autoload.php';

  $dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__);
  $dotenv->load();

  // Função para autenticar e obter o token OAuth2
  function getAccessToken($client_id, $client_secret) {
    // URL do endpoint OAuth2 de autenticação no Banco do Brasil (ambiente de homologação)
    $url = "https://oauth.hm.bb.com.br/oauth/token"; 
    // O `client_id` e `client_secret` são codificados em base64 para serem passados no header Authorization
    $auth = base64_encode("$client_id:$client_secret");

    // Parâmetros da requisição: estamos solicitando um token OAuth2 com o grant_type 'client_credentials'
    // e os escopos de autorização específicos para gerar boletos
    $data = [
        "grant_type" => "client_credentials",
        "scope" => "cobrancas.boletos-info cobrancas.boletos-requisicao"
    ];

    // Configura as opções da requisição HTTP
    $options = [
        "http" => [
            // Header Authorization (autenticação) e Content-Type (tipo de dado que estamos enviando)
            "header" => "Authorization: Basic $auth\r\n" .
                        "Content-Type: application/x-www-form-urlencoded\r\n",
            // Método POST, que será usado para enviar a requisição
            "method" => "POST",
            // Converte os dados para o formato esperado pela API
            "content" => http_build_query($data),
        ]
    ];

    // Cria o contexto da requisição HTTP com as opções configuradas
    $context = stream_context_create($options);
    // Envia a requisição para o endpoint de autenticação e captura a resposta
    $response = file_get_contents($url, false, $context);

    // Decodifica a resposta JSON para obter o token de acesso
    $token = json_decode($response, true);
    // Retorna apenas o campo 'access_token', que será usado para autenticar as próximas requisições
    return $token['access_token'];
  }

  // Função para criar um boleto
  function criarBoleto($token, $app_key) {
    $url = "https://api.hm.bb.com.br/cobrancas/v2/boletos?gw-dev-app-key=$app_key";

    // Obter a data atual e adicionar 30 dias para o vencimento
    $dataEmissao = date('Y-m-d');  // Data atual no formato YYYY-MM-DD
    $dataVencimento = date('Y-m-d', strtotime($dataEmissao . ' + 30 days'));  // Data de vencimento 30 dias à frente

    $data = [
        "numeroConvenio" => "3128557",
        "numeroCarteira" => "17",
        "numeroVariacaoCarteira" => "35",
        "codigoModalidade" => 1,
        "dataEmissao" => $dataEmissao,  // Data de emissão dinâmica
        "dataVencimento" => $dataVencimento,  // Data de vencimento dinâmica
        "valorOriginal" => 123.45,
        "pagador" => [
            "tipoInscricao" => 1,
            "numeroInscricao" => "96050176876",
            "nome" => "VALERIO DE AGUIAR ZORZATO",
            "endereco" => "Rua Fictícia, 123",
            "cep" => "01001000",
            "cidade" => "São Paulo",
            "bairro" => "Centro",
            "uf" => "SP",
            "telefone" => "11999999999"
        ],
        "numeroTituloBeneficiario" => "123456",
        "codigoAceite" => "A",
        "indicadorPix" => "S"
    ];

    // Mostrar o corpo da requisição JSON para depuração
    echo "JSON Enviado:\n";
    echo json_encode($data, JSON_PRETTY_PRINT);
    echo "\n\n";

    $options = [
        "http" => [
            "header" => "Authorization: Bearer $token\r\n" .
                        "Content-Type: application/json\r\n",
            "method" => "POST",
            "content" => json_encode($data),
            "ignore_errors" => true
        ]
    ];

    $context = stream_context_create($options);

    // Capturar a resposta com mais detalhes sobre erros
    $response = @file_get_contents($url, false, $context);
    
    // Verifica se houve erro na requisição
    if ($response === FALSE) {
        $error = error_get_last();
        print_r($error);
        if (isset($http_response_header)) {
            echo "HTTP Response Header:\n";
            print_r($http_response_header);  // Exibir cabeçalhos da resposta HTTP para mais detalhes
        }
    } else {
        // Retorna a resposta da API
        return json_decode($response, true);
    }
}




  // Exemplo de uso do código:
  // Substitua com os valores reais do seu ambiente de desenvolvimento ou produção
  $client_id = $_SERVER['SEU_CLIENT_ID'];               // Seu client_id fornecido pelo Banco do Brasil
  $client_secret = $_SERVER['SEU_CLIENT_SECRET'];       // Seu client_secret fornecido pelo Banco do Brasil
  $app_key = $_SERVER['APP_KEY'];                     // Sua chave de aplicação (app_key) do Banco do Brasil

  // Obter o token de acesso OAuth2
  $token = getAccessToken($client_id, $client_secret);
  // Criar o boleto com o token obtido e a app_key
  $respostaBoleto = criarBoleto($token, $app_key);

  // Exibir a resposta da criação do boleto
  print_r($respostaBoleto);

?>