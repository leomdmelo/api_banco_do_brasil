<?php
// Carrega o autoload do Composer para carregar automaticamente as dependências
require 'v_ambiente/autoload.php';

// Carrega as variáveis de ambiente (como client_id, client_secret e app_key) do arquivo .env
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__); // Carrega as variáveis de ambiente de forma insegura (não recomendado para produção)
$dotenv->load();


// 1. Obter o Token OAuth 2.0
// Função para autenticar e obter o token OAuth2
function getAccessToken($client_id, $client_secret) {
  // URL do endpoint OAuth2 de autenticação no Banco do Brasil (ambiente de homologação)
  $url = "https://oauth.hm.bb.com.br/oauth/token"; 
  
  // Cria a autenticação básica em base64 (necessária para o header Authorization)
  $auth = base64_encode("$client_id:$client_secret");

  // Parâmetros da requisição para obter o token OAuth2
  $data = [
      "grant_type" => "client_credentials",  // Tipo de autorização: "client_credentials"
      "scope" => "cobrancas.boletos-info cobrancas.boletos-requisicao" // Escopos de acesso para boletos
  ];

  // Configuração da requisição HTTP
  $options = [
      "http" => [
          "header" => "Authorization: Basic $auth\r\n" . // Cabeçalho de autorização usando o client_id e client_secret
                      "Content-Type: application/x-www-form-urlencoded\r\n", // Tipo de dado enviado
          "method" => "POST", // Método HTTP POST
          "content" => http_build_query($data), // Converte os dados para o formato URL-encoded
      ]
  ];

  // Cria o contexto da requisição HTTP
  $context = stream_context_create($options);
  
  // Envia a requisição e obtém a resposta
  $response = file_get_contents($url, false, $context);

  // Decodifica a resposta JSON para extrair o token
  $token = json_decode($response, true);
  
  // Retorna o campo 'access_token' da resposta
  return $token['access_token'];
}

// Função para cancelar/baixar um boleto
function cancelarBoleto($token, $app_key, $numeroConvenio, $numeroBoleto) {
    $url = "https://api.hm.bb.com.br/cobrancas/v2/boletos/$numeroBoleto/baixar";
    $query = http_build_query(["gw-dev-app-key" => $app_key]);

    $body = json_encode([
        "numeroConvenio" => $numeroConvenio
    ]);

    $options = [
        "http" => [
            "header" => "Authorization: Bearer $token\r\n" .
                        "Content-Type: application/json\r\n",
            "method" => "POST",
            "content" => $body,
        ]
    ];

    $context = stream_context_create($options);
    $response = @file_get_contents($url . "?" . $query, false, $context);

    if ($response === false) {
        return ["erro" => "Não foi possível cancelar o boleto. Verifique o número do boleto e tente novamente."];
    }

    return json_decode($response, true);
}

// Exemplo de uso do código
$client_id = $_SERVER['SEU_CLIENT_ID'];               // Seu client_id fornecido pelo Banco do Brasil
$client_secret = $_SERVER['SEU_CLIENT_SECRET'];       // Seu client_secret fornecido pelo Banco do Brasil
$app_key = $_SERVER['APP_KEY'];                       // Sua app_key fornecida pelo Banco do Brasil
$numeroConvenio = "3128557";                          // Número do convênio
$numeroBoleto = "00031285579900001147";               // Número do boleto que deseja cancelar

// Obter o token de acesso OAuth2
$token = getAccessToken($client_id, $client_secret);

// Cancelar/baixar o boleto
$resultado = cancelarBoleto($token, $app_key, $numeroConvenio, $numeroBoleto);

// Exibir o resultado
if (isset($resultado['erro'])) {
    echo $resultado['erro'];
} else {
    echo "Boleto cancelado com sucesso!";
    echo "<pre>";
    print_r($resultado);
    echo "</pre>";
}

?>
