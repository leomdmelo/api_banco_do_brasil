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

function criarBoleto($token, $app_key) {
    $url = "https://api.hm.bb.com.br/cobrancas/v2/boletos?gw-dev-app-key=$app_key";
    $dataEmissao = date('d.m.Y');
    $dataVencimento = date('d.m.Y', strtotime($dataEmissao . ' + 15 days'));
    $data = [
        "numeroConvenio" => "3128557",
        "numeroCarteira" => "17",
        "numeroVariacaoCarteira" => "35",
        "codigoModalidade" => 1,
        "dataEmissao" => $dataEmissao,
        "dataVencimento" => $dataVencimento,
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
        "indicadorPix" => "S",
        "numeroTituloCliente" => "00031285579900001149"
    ];
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

$token = getAccessToken($client_id, $client_secret);
$respostaBoleto = criarBoleto($token, $app_key);

print_r($respostaBoleto);

if (isset($respostaBoleto['numero'])) {
    $linhaDigitavel = $respostaBoleto['linhaDigitavel'];
    $codigoBarras = $respostaBoleto['codigoBarraNumerico'];
    $codigoPix = $respostaBoleto['qrCode']['emv'];
    echo "
    <html>
    <head>
        <title>Boleto Gerado</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .boleto-info { margin-bottom: 20px; }
            .boleto-info div { margin-bottom: 10px; }
            .boleto-info strong { display: inline-block; width: 150px; }
            .qrcode { margin-top: 20px; }
        </style>
    </head>
    <body>
        <h1>Boleto Gerado com Sucesso</h1>
        <div class='boleto-info'>
            <div><strong>Codigo do PIX:</strong> $codigoPix</div>
            <div><strong>Linha Digitável:</strong> $linhaDigitavel</div>
            <div><strong>Código de Barras:</strong> $codigoBarras</div>
        </div>
    </body>
    </html>
    ";
} else {
    echo "<h1>Erro ao gerar o boleto</h1>";
    echo "<p>Por favor, tente novamente mais tarde.</p>";
}

/*

Boleto Gerado com Sucesso
Codigo do PIX: 00020101021226920014br.gov.bcb.pix2570qrcodepix-h.bb.com.br/pix/v2/cobv/4b430a16-da56-4f2c-b3a5-4088a3c7fd6b5204000053039865406123.455802BR5925MERCEARIA MANASSES PEREIR6008BRASILIA62070503***6304EDAF
Linha Digitável: 00190000090312855799200001148170898560000012345
Código de Barras: 00198985600000123450000003128557990000114817


*/


// Função para autenticar e obter o token OAuth2
/*function getAccessToken($client_id, $client_secret) {
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

// Função para criar um boleto
function criarBoleto($token, $app_key) {
    // URL da API do Banco do Brasil para gerar boletos
    $url = "https://api.hm.bb.com.br/cobrancas/v2/boletos?gw-dev-app-key=$app_key";

    // Obtem a data de emissão (hoje) e a data de vencimento (15 dias à frente)
    $dataEmissao = date('d.m.Y'); // Formata a data atual
    $dataVencimento = date('d.m.Y', strtotime($dataEmissao . ' + 15 days')); // Calcula a data de vencimento

    // Dados do boleto a serem enviados
    $data = [
        "numeroConvenio" => "3128557", // Número do convênio com o Banco do Brasil
        "numeroCarteira" => "17", // Número da carteira
        "numeroVariacaoCarteira" => "35", // Variação da carteira
        "codigoModalidade" => 1, // Modalidade da cobrança
        "dataEmissao" => $dataEmissao,  // Data de emissão
        "dataVencimento" => $dataVencimento,  // Data de vencimento
        "valorOriginal" => 123.45, // Valor do boleto
        "pagador" => [  // Informações do pagador
            "tipoInscricao" => 1,  // Tipo de inscrição (1 = CPF, 2 = CNPJ)
            "numeroInscricao" => "96050176876",  // CPF do pagador
            "nome" => "VALERIO DE AGUIAR ZORZATO",  // Nome do pagador
            "endereco" => "Rua Fictícia, 123",  // Endereço do pagador
            "cep" => "01001000",  // CEP do pagador
            "cidade" => "São Paulo",  // Cidade do pagador
            "bairro" => "Centro",  // Bairro do pagador
            "uf" => "SP",  // Estado do pagador
            "telefone" => "11999999999"  // Telefone do pagador
        ],
        "numeroTituloBeneficiario" => "123456",  // Número do título do beneficiário
        "codigoAceite" => "A",  // Código de aceite
        "indicadorPix" => "S",  // Indicador de geração de QR Code PIX
        "numeroTituloCliente" => "00031285579900001147"  // Nosso Número
    ];

    // Exibe o JSON enviado para depuração
    echo "JSON Enviado:\n";
    echo json_encode($data, JSON_PRETTY_PRINT);
    echo "\n\n";

    // Configura a requisição HTTP para a API de boletos
    $options = [
        "http" => [
            "header" => "Authorization: Bearer $token\r\n" . // Cabeçalho de autorização com o token OAuth2
                        "Content-Type: application/json\r\n", // Tipo de dado enviado
            "method" => "POST", // Método HTTP POST
            "content" => json_encode($data), // Dados em formato JSON
            "ignore_errors" => true // Ignora erros HTTP (para capturar o conteúdo de erro)
        ]
    ];

    // Cria o contexto da requisição HTTP
    $context = stream_context_create($options);

    // Envia a requisição e captura a resposta
    $response = @file_get_contents($url, false, $context);
    
    // Verifica se houve erro na requisição
    if ($response === FALSE) {
        $error = error_get_last(); // Obtém o último erro
        print_r($error); // Exibe detalhes do erro
        if (isset($http_response_header)) {
            echo "HTTP Response Header:\n";
            print_r($http_response_header);  // Exibe os cabeçalhos da resposta HTTP
        }
    } else {
        // Retorna a resposta da API decodificada
        return json_decode($response, true);
    }
}

// Exemplo de uso do código:
// As variáveis abaixo são obtidas do ambiente ou de configurações do sistema
$client_id = $_SERVER['SEU_CLIENT_ID'];  // Seu client_id fornecido pelo Banco do Brasil
$client_secret = $_SERVER['SEU_CLIENT_SECRET'];  // Seu client_secret fornecido pelo Banco do Brasil
$app_key = $_SERVER['APP_KEY'];  // Sua chave de aplicação (app_key) do Banco do Brasil

// Obter o token de acesso OAuth2
$token = getAccessToken($client_id, $client_secret);

// Criar o boleto com o token obtido e a app_key
$respostaBoleto = criarBoleto($token, $app_key);

// Exibir a resposta da criação do boleto
print_r($respostaBoleto);

// Se o boleto foi criado com sucesso, exibe os dados relevantes
if (isset($respostaBoleto['numero'])) {
    // Dados do boleto gerado
    $linhaDigitavel = $respostaBoleto['linhaDigitavel']; // Linha digitável do boleto
    $codigoBarras = $respostaBoleto['codigoBarraNumerico']; // Código de barras numérico
    $codigoPix = $respostaBoleto['qrCode']['emv']; // Código PIX gerado

    // Exibe os dados do boleto em formato HTML para o cliente
    echo "
    <html>
    <head>
        <title>Boleto Gerado</title>
        <style>
            body { font-family: Arial, sans-serif; padding: 20px; }
            .boleto-info { margin-bottom: 20px; }
            .boleto-info div { margin-bottom: 10px; }
            .boleto-info strong { display: inline-block; width: 150px; }
            .qrcode { margin-top: 20px; }
        </style>
    </head>
    <body>
        <h1>Boleto Gerado com Sucesso</h1>

        <div class='boleto-info'>
            <div><strong>Codigo do PIX:</strong> $codigoPix</div>
            <div><strong>Linha Digitável:</strong> $linhaDigitavel</div>
            <div><strong>Código de Barras:</strong> $codigoBarras</div>
        </div>
    </body>
    </html>
    ";
} else {
    // Em caso de erro, exibe uma mensagem para o cliente
    echo "<h1>Erro ao gerar o boleto</h1>";
    echo "<p>Por favor, tente novamente mais tarde.</p>";
}*/

?>
