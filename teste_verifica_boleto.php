<?php
// Carrega o autoload do Composer para carregar automaticamente as dependências
require 'v_ambiente/autoload.php';

// Carrega as variáveis de ambiente (como client_id, client_secret e app_key) do arquivo .env
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__); // Carrega as variáveis de ambiente de forma insegura (não recomendado para produção)
$dotenv->load();

$client_id = $_SERVER['SEU_CLIENT_ID'];
$client_secret = $_SERVER['SEU_CLIENT_SECRET'];
$app_key = $_SERVER['APP_KEY'];

// Função para obter o token de autenticação OAuth 2.0
function getAccessToken($client_id, $client_secret) {
    $url = "https://oauth.bb.com.br/oauth/token"; 
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

function consultarBoleto($token, $app_key, $idBoleto, $numeroConvenio) {
    $url = "https://api.bb.com.br/cobrancas/v2/boletos/$idBoleto?gw-dev-app-key=$app_key&numeroConvenio=$numeroConvenio";
    
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
    
    // Verifica se houve erro na requisição
    if ($response === FALSE) {
        $error = error_get_last();
        print_r($error);
        if (isset($http_response_header)) {
            echo "HTTP Response Header:\n";
            print_r($http_response_header);
        }
        return null; // Retorna null em caso de erro
    } else {
        $dadosBoleto = json_decode($response, true);
        
        // Verifica se houve erro na resposta da API
        if (isset($dadosBoleto['errors'])) {
            foreach ($dadosBoleto['errors'] as $error) {
                echo "Erro: " . $error['message'] . "\n";
            }
            return null; // Retorna null se houver erros
        }
        
        // Retorna os dados do boleto
        return $dadosBoleto;
    }
}




// Exemplo de uso para consultar um boleto
$idBoleto = "00030029140000000101";  // ID ou Nosso Número do boleto que deseja consultar
$numeroConvenio = "3002914";
$token = getAccessToken($client_id, $client_secret);

$detalhesBoleto = consultarBoleto($token, $app_key, $idBoleto, $numeroConvenio);

if ($detalhesBoleto !== null) {
    // Processa os detalhes do boleto
    echo "Estado do Boleto: " . $detalhesBoleto['codigoEstadoTituloCobranca'] . "\n";
    echo "Valor Pago: " . $detalhesBoleto['valorPagoSacado'] . "\n";
    echo "Data do Pagamento: " . $detalhesBoleto['dataRecebimentoTitulo'] . "\n";
    echo "<br>";
    echo print_r($detalhesBoleto);
} else {
    echo "Não foi possível consultar o boleto.\n";
}


?>