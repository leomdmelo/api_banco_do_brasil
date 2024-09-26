<?php

// Carrega o autoload do Composer para carregar automaticamente as dependências
require 'v_ambiente/autoload.php';

// Carrega as variáveis de ambiente (como client_id, client_secret e app_key) do arquivo .env
$dotenv = Dotenv\Dotenv::createUnsafeImmutable(__DIR__); // Carrega as variáveis de ambiente de forma insegura (não recomendado para produção)
$dotenv->load();

// Função para obter o token OAuth2 (usada no código anterior)
function getAccessToken($client_id, $client_secret) {
    $url = "https://oauth.bb.com.br/oauth/token";
    $auth = base64_encode("$client_id:$client_secret");

    $data = [
        "grant_type" => "client_credentials",
        "scope" => "cobrancas.boletos-info"
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

// Função para listar boletos
function listarBoletos($token, $app_key, $params) {
    // Endpoint da API de listagem de boletos
    $url = "https://api.bb.com.br/cobrancas/v2/boletos";

    // Constrói a query string com os parâmetros fornecidos
    $query = http_build_query(array_merge(["gw-dev-app-key" => $app_key], $params));

    // URL completa
    $full_url = $url . "?" . $query;

    // Configura as opções da requisição GET
    $options = [
        "http" => [
            "header" => "Authorization: Bearer $token\r\n" .
                        "Content-Type: application/json\r\n",
            "method" => "GET",
        ]
    ];

    // Cria o contexto da requisição HTTP
    $context = stream_context_create($options);
    // Faz a requisição e captura a resposta
    $response = @file_get_contents($full_url, false, $context);

    // Verifica se ocorreu algum erro (ex: 404)
    if ($response === false) {
        return ["erro" => "Não foi possível obter os boletos. Verifique os parâmetros."];
    }

    // Retorna o JSON decodificado com a lista de boletos
    return json_decode($response, true);
}

// Exemplo de uso do código
$client_id = $_SERVER['SEU_CLIENT_ID'];               // Seu client_id fornecido pelo Banco do Brasil
$client_secret = $_SERVER['SEU_CLIENT_SECRET'];       // Seu client_secret fornecido pelo Banco do Brasil
$app_key = $_SERVER['APP_KEY'];                       // Sua chave de aplicação (app_key) do Banco do Brasil

// Parâmetros da consulta (substitua com os valores reais)
$params = [
    "indicadorSituacao" => "A",          // Boletos em ser (ou "B" para baixados/liquidados/protestados)
    "agenciaBeneficiario" => "3568",      // Número da agência do beneficiário
    "contaBeneficiario" => "29859",     // Número da conta do beneficiário
    "dataInicioVencimento" => "01.01.2020", // Data inicial de vencimento
    "dataFimVencimento" => "23.12.2024"  // Data final de vencimento
    // Outros parâmetros podem ser adicionados aqui conforme necessário
];

// Obter o token de acesso OAuth2
$token = getAccessToken($client_id, $client_secret);

// Listar os boletos com os parâmetros fornecidos
$listaBoletos = listarBoletos($token, $app_key, $params);

// Exibir a resposta da listagem dos boletos
//print_r($listaBoletos);

// Exibir a lista de boletos de forma organizada
if (isset($listaBoletos['erro'])) {
  echo $listaBoletos['erro'];
} else {
  if (empty($listaBoletos['boletos'])) {
      echo "Nenhum boleto encontrado.";
  } else {
      echo "<table border='1'>";
      echo "<tr>
              <th>Nosso Número</th>
              <th>Data de Emissão</th>
              <th>Data de Vencimento</th>
              <th>Valor</th>
              <th>Situação</th>
            </tr>";

      foreach ($listaBoletos['boletos'] as $boleto) {
          echo "<tr>";
          echo "<td>" . htmlspecialchars($boleto['numeroBoletoBB']) . "</td>";
          echo "<td>" . htmlspecialchars($boleto['dataRegistro']) . "</td>";
          echo "<td>" . htmlspecialchars($boleto['dataVencimento']) . "</td>";
          echo "<td>" . htmlspecialchars(number_format($boleto['valorOriginal'], 2, ',', '.')) . "</td>";
          echo "<td>" . htmlspecialchars($boleto['codigoEstadoTituloCobranca']) . "</td>";
          echo "</tr>";
      }

      echo "</table>";
  }
}


?>
