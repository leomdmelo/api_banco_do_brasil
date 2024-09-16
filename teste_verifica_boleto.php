<?php
// Carrega o autoload do Composer para carregar automaticamente as dependências
require 'v_ambiente/autoload.php';

// Carrega as variáveis de ambiente (como client_id, client_secret e app_key) do arquivo .env
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__); // Carrega as variáveis de ambiente de forma insegura (não recomendado para produção)
$dotenv->load();

function getAccessToken($client_id, $client_secret) {
    $url = "https://oauth.hm.bb.com.br/oauth/token"; 
    $auth = base64_encode("$client_id:$client_secret");
    $data = [
        "grant_type" => "client_credentials",
        "scope" => "cobrancas.boletos-info cobrancas.boletos-requisicao"
    ];
    $options = [
        "http" => [
            "header" => "Authorization: Basic $auth\r\n" .
                        "Content-Type: application/x-www-form-urlencoded\r\n",
            "method" => "POST",
            "content" => http_build_query($data),
        ]
    ];
    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);
    $token = json_decode($response, true);
    return $token['access_token'];
}

function consultarBoleto($token, $app_key, $id) {
  $url = "https://api.hm.bb.com.br/cobrancas/v2/boletos/$id?gw-dev-app-key=$app_key";
  
  $options = [
      "http" => [
          "header" => "Authorization: Bearer $token\r\n" .
                      "Content-Type: application/json\r\n",
          "method" => "GET",
          "ignore_errors" => true
      ]
  ];
  
  $context = stream_context_create($options);
  $response = @file_get_contents($url, false, $context);
  
  if ($response === FALSE) {
      $error = error_get_last();
      print_r($error);
      if (isset($http_response_header)) {
          echo "HTTP Response Header:\n";
          print_r($http_response_header);
      }
  } else {
      return json_decode($response, true);
  }
}

$client_id = $_SERVER['SEU_CLIENT_ID'];
$client_secret = $_SERVER['SEU_CLIENT_SECRET'];
$app_key = $_SERVER['APP_KEY'];

// Exemplo de uso para consultar um boleto
$id = "00031285570000003000";  // ID ou Nosso Número do boleto que deseja consultar
$token = getAccessToken($client_id, $client_secret);
$respostaBoleto = consultarBoleto($token, $app_key, $id);

// Verifica se o boleto foi pago
if (isset($respostaBoleto['situacao']) && $respostaBoleto['situacao'] === 'PAGO') {
  $valorPago = $respostaBoleto['valorPago'];
  $dataPagamento = $respostaBoleto['dataPagamento'];
  echo "Boleto pago no valor de R$ $valorPago em $dataPagamento.\n";
} else {
  echo "Boleto ainda não foi pago.\n";
}

?>