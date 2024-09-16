<?php
// Carrega o autoload do Composer para carregar automaticamente as dependências
require 'v_ambiente/autoload.php';

// Carrega as variáveis de ambiente (como client_id, client_secret e app_key) do arquivo .env
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__); // Carrega as variáveis de ambiente de forma insegura (não recomendado para produção)
$dotenv->load();

// As variáveis abaixo são obtidas do ambiente ou de configurações do sistema
$client_id = $_SERVER['SEU_CLIENT_ID'];  // Seu client_id fornecido pelo Banco do Brasil
$client_secret = $_SERVER['SEU_CLIENT_SECRET'];  // Seu client_secret fornecido pelo Banco do Brasil
$app_key = $_SERVER['APP_KEY'];  // Sua chave de aplicação (app_key) do Banco do Brasil
$boleto = '00031285579900001148';

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

// Obter o token de acesso OAuth2
$token = getAccessToken($client_id, $client_secret);

// 2. Alterar o boleto

// URL da API em homologação (substitua o ID do boleto correto)
$url = 'https://api.hm.bb.com.br/cobrancas/v2/boletos/'.$boleto;

// Headers da requisição
$headers = [
    "Authorization: Bearer $token",
    "Content-Type: application/json",
    "gw-dev-app-key: $app_key"  // Substitua por sua appKey correta
];

// Dados a serem enviados no corpo da requisição (Payload)
$payload = [
    "numeroConvenio" => "3128557",
    "indicadorNovoValorNominal" => "S",
    "novoValorNominal" => 200.50 // Novo valor do boleto
];

// Inicializa o cURL para alterar o boleto
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

// Executa a requisição
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Verifica se houve sucesso ou erro
if ($http_code == 200) {
    echo "Boleto alterado com sucesso!";
    echo $response;  // Exibe a resposta da API
} else {
    echo "Erro ao alterar o boleto: $response";
}

// Fecha a conexão cURL
curl_close($ch);
?>
