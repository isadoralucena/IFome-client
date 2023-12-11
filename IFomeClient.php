<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException;

class IFomeClient
{
    private $ifome_client;
    private $suap_client;

    public function __construct(private string $suap_token = '', private $logado = false)
    {
        try {
            $this->ifome_client = new GuzzleClient([
                'base_uri' => 'http://localhost:8000/api/',
            ]);
            $this->suap_client = new GuzzleClient([
                'base_uri' => 'https://suap.ifrn.edu.br/api/v2/'
            ]);
        } catch (Exception $e) {
            $this->handleApiConnectionError($e);
        }
    }
    private function handleApiConnectionError(Exception $e)
    {
        echo "Erro na conexão com as APIs: " . $e->getMessage() . "\n";
    }

    private function criarTokenSUAP($matricula, $senha): string {
        
        $params = [
            'form_params' => [
                'username' => $matricula,
                'password' => $senha
            ]
        ];
        
        $resp = $this->suap_client->post(
            '/api/v2/autenticacao/token/',
            $params
        );

        $resp_json = json_decode($resp->getBody());
        
        $token = $resp_json->access;

        return $token;
    }

    public function login($matricula, $senha): array {
        $this->suap_token = $this->criarTokenSUAP($matricula, $senha);

        $usuario = $this->getDadosUsuarioSUAP();
        $usuario['suap_token'] = $this->suap_token;

        $this->logado = true;

        return $usuario;
    }

    private function getDadosUsuarioSUAP(): array {
        $res = json_decode(
            $this->suap_client->get(
                'minhas-informacoes/meus-dados/',
                ['headers' => ['Authorization' => "Bearer $this->suap_token"]]
            )->getBody()->getContents(),
            associative: true
        );

        $dados = [
            'nome' => $res['nome_usual'],
            'matricula' => $res['matricula']
        ];

        return $dados;
    }

    private function validateAlimento($data)
    {
        $errors = "";

        if (empty($data['nome']) || strlen($data['nome']) < 3) {
            $errors .= "Nome é obrigatório e deve ter pelo menos 3 caracteres.\n";
        }

        if (empty($data['composicao'])) {
            $errors .= "Composição é obrigatória.\n";
        }

        if (!filter_var($data['quantidade_estoque'], FILTER_VALIDATE_INT) || $data['quantidade_estoque'] < 0) {
            $errors .= "Quantidade em estoque deve ser um número inteiro não negativo.\n";
        }

        if (!filter_var($data['valor'], FILTER_VALIDATE_FLOAT) || $data['valor'] < 0) {
            $errors .= "Valor deve ser um número não negativo.\n";
        }

        return $errors;
    }

    private function validateBebida($data)
    {
        $errors = "";

        if (empty($data['nome']) || strlen($data['nome']) < 3) {
            $errors .= "Nome é obrigatório e deve ter pelo menos 3 caracteres.\n";
        }

        if (!filter_var($data['quantidade_estoque'], FILTER_VALIDATE_INT) || $data['quantidade_estoque'] < 0) {
            $errors .= "Quantidade em estoque deve ser um número inteiro não negativo.\n";
        }

        if (!filter_var($data['valor'], FILTER_VALIDATE_FLOAT) || $data['valor'] < 0) {
            $errors .= "Valor deve ser um número não negativo.\n";
        }

        return $errors;
    }

    public function getAlimentos()
    {
        try {
            $response = $this->ifome_client->get('alimentos');
            $alimentos = json_decode($response->getBody(), true);
            $this->displayItems($alimentos, 'Alimentos');
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function getAlimentoById($id)
    {
        try {
            $response = $this->ifome_client->get("alimentos/{$id}");
            $alimento = json_decode($response->getBody(), true);
    
            if (!empty($alimento)) {
                $this->displayItem($alimento, 'Detalhes do Alimento');
            } else {
                echo "Alimento não encontrado.\n";
            }
    
            return $alimento;
        } catch (RequestException $e) {
            $this->handleException($e);
            return null;
        }
      
    }

    public function cadastrarAlimento()
    {

        if (!$this->logado) {
            echo "Você precisa realizar o login primeiro.\n";
            return;
        }

        echo "Informe os dados do novo alimento:\n";

        $nome = $this->getUserInput("Nome: ");
        $composicao = $this->getUserInput("Composição: ");
        $quantidade_estoque = $this->getUserInput("Quantidade em estoque: ");
        $valor = $this->getUserInput("Valor: ");

        $quantidade_estoque = intval($quantidade_estoque);
        $valor = floatval($valor);

        $novoAlimento = [
            'nome' => $nome,
            'composicao' => $composicao,
            'quantidade_estoque' => $quantidade_estoque,
            'valor' => $valor,
        ];

        $validationErrors = $this->validateAlimento($novoAlimento);

        if (!empty($validationErrors)) {
            echo "Erros de validação:\n";
            echo $validationErrors;
            return;
        }

        try {
            $response = $this->ifome_client->post('alimentos', [
                'json' => $novoAlimento,
                'headers' => ['Authorization' => "Bearer $this->suap_token"]
            ]);
            echo "Alimento cadastrado com sucesso.\n";
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }


    public function cadastrarBebida()
    {

        if (!$this->logado) {
            echo "Você precisa realizar o login primeiro.\n";
            return;
        }

        echo "Informe os dados da nova Bebida:\n";

        $nome = $this->getUserInput("Nome: ");
        $quantidade_estoque = $this->getUserInput("Quantidade em estoque: ");
        $valor = $this->getUserInput("Valor: ");

        $quantidade_estoque = intval($quantidade_estoque);
        $valor = floatval($valor);

        $novaBebida = [
            'nome' => $nome,
            'quantidade_estoque' => $quantidade_estoque,
            'valor' => $valor,
        ];

        $validationErrors = $this->validateBebida($novaBebida);

        if (!empty($validationErrors)) {
            echo "Erros de validação:\n";
            echo $validationErrors;
            return;
        }

        try {
            $response = $this->ifome_client->post('bebidas', [
                'json' => $novaBebida,
                'headers' => ['Authorization' => "Bearer $this->suap_token"]
            ]);
            echo "Bebida cadastrada com sucesso.\n";
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function editarAlimento()
    {

        if (!$this->logado) {
            echo "Você precisa realizar o login primeiro.\n";
            return;
        }

        $id = intval($this->getUserInput("Digite o ID do Alimento a ser editado: "));

        try {
            $alimentoAntesEdicao = $this->getAlimentoById($id);

            if (empty($alimentoAntesEdicao)) {
                echo "Alimento não encontrado.\n";
                return;
            }

            echo "Informe os novos dados do Alimento:\n";
            $alimentoEditado = [
                'nome' => $this->getUserInput("Novo Nome: "),
                'composicao' => $this->getUserInput("Nova Composição: "),
                'quantidade_estoque' => intval($this->getUserInput("Nova Quantidade em estoque: ")),
                'valor' => floatval($this->getUserInput("Novo Valor: ")),
            ];

            $dadosCombinados = array_merge($alimentoAntesEdicao, $alimentoEditado);

            $validationErrors = $this->validateAlimento($dadosCombinados);

            if (!empty($validationErrors)) {
                echo "Erros de validação:\n";
                echo $validationErrors;
                return;
            }

            $response = $this->ifome_client->put("alimentos/{$id}", [
                'json' => $alimentoEditado,
                'headers' => ['Authorization' => "Bearer $this->suap_token"]
            ]);

            echo "Alimento editado com sucesso.\n";
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }


    public function excluirAlimento()
    {

        if (!$this->logado) {
            echo "Você precisa realizar o login primeiro.\n";
            return;
        }

        $id = intval($this->getUserInput("Digite o ID do Alimento a ser excluído: "));

        try {
            $response = $this->ifome_client->delete("alimentos/{$id}",[
                'headers' => ['Authorization' => "Bearer $this->suap_token"]
            ]);
            echo "Alimento excluído com sucesso.\n";
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function getBebidas()
    {
        try {
            $response = $this->ifome_client->get('bebidas');
            $bebidas = json_decode($response->getBody(), true);
            $this->displayItems($bebidas, 'Bebidas');
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function getBebidaById($id)
    {
        try {
            $response = $this->ifome_client->get("bebidas/{$id}");
            $bebida = json_decode($response->getBody(), true);
    
            if (!empty($bebida)) {
                $this->displayItem($bebida, 'Detalhes da Bebida');
            } else {
                echo "Bebida não encontrada.\n";
            }
    
            return $bebida;
        } catch (RequestException $e) {
            $this->handleException($e);
            return null;
        }
    
    }

    public function editarBebida()
    {

        if (!$this->logado) {
            echo "Você precisa realizar o login primeiro.\n";
            return;
        }

        $id = intval($this->getUserInput("Digite o ID da Bebida a ser editada: "));

        try {
            $bebidaAntesEdicao = $this->getBebidaById($id);

            if (empty($bebidaAntesEdicao)) {
                echo "Bebida não encontrada.\n";
                return;
            }

            echo "Informe os novos dados da Bebida:\n";
            $bebidaEditada = [
                'nome' => $this->getUserInput("Novo Nome: "),
                'quantidade_estoque' => intval($this->getUserInput("Nova Quantidade em estoque: ")),
                'valor' => floatval($this->getUserInput("Novo Valor: ")),
            ];

            $dadosCombinados = array_merge($bebidaAntesEdicao, $bebidaEditada);

            $validationErrors = $this->validateBebida($dadosCombinados);

            if (!empty($validationErrors)) {
                echo "Erros de validação:\n";
                echo $validationErrors;
                return;
            }

            $response = $this->ifome_client->put("bebidas/{$id}", [
                'json' => $bebidaEditada,
                'headers' => ['Authorization' => "Bearer $this->suap_token"]
            ]);

            echo "Bebida editada com sucesso:\n";
            $this->displayItem($bebidaEditada, '');
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function excluirBebida()
    {

        if (!$this->logado) {
            echo "Você precisa realizar o login primeiro.\n";
            return;
        }

        $id = intval($this->getUserInput("Digite o ID da Bebida a ser excluída: "));

        try {
            $response = $this->ifome_client->delete("bebidas/{$id}",[
                'headers' => ['Authorization' => "Bearer $this->suap_token"]
            ]);
            echo "Bebida excluída com sucesso.\n";
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function showMenu()
    {
        echo "Escolha uma opção:\n";
        if (!$this->logado) {
            echo "1. Realizar login\n";
        }
        echo "2. Listar Alimentos\n";
        echo "3. Obter detalhes de um Alimento\n";
        echo "4. Cadastrar Alimento\n";
        echo "5. Editar Alimento\n";
        echo "6. Excluir Alimento\n";
        echo "7. Listar Bebidas\n";
        echo "8. Obter detalhes de uma Bebida\n";
        echo "9. Cadastrar Bebida\n";
        echo "10. Editar Bebida\n";
        echo "11. Excluir Bebida\n";
        echo "0. Sair\n";
    }

    public function run()
    {
        $opcao = -1;

        do {
            $this->exibirTitulo();
            $this->showMenu();

            echo "Digite a opção desejada: ";
            $opcao = intval(trim(fgets(STDIN)));

            switch ($opcao) {
                case 1:
                    echo "Digite sua matricula:\n";
                    $matricula = readline();

                    echo "Digite sua senha (será oculta): ";
                    try {
                        $senha = Seld\CliPrompt\CliPrompt::hiddenPrompt();
                    } catch (Exception $e) {
                        echo "Erro ao obter a senha oculta.\n";
                        return;
                    }            
                    $this->login($matricula, $senha);
                    break;

                case 2:
                    $this->getAlimentos();
                    break;

                case 3:
                    $id = intval($this->getUserInput("Digite o ID do Alimento: "));
                    $this->getAlimentoById($id);
                    break;

                case 4:
                    $this->cadastrarAlimento();
                    break;

                case 5:
                    $this->editarAlimento();
                    break;

                case 6:
                    $this->excluirAlimento();
                    break;

                case 7:
                    $this->getBebidas();
                    break;

                case 8:
                    $id = intval($this->getUserInput("Digite o ID da Bebida: "));
                    $this->getBebidaById($id);
                    break;

                case 9:
                    $this->cadastrarBebida();
                    break;

                case 10:
                    $this->editarBebida();
                    break;
                
                case 11:
                    $this->excluirBebida();
                    break;

                case 0:
                    echo "Saindo...\n";
                    break;

                default:
                    echo "Opção inválida. Tente novamente.\n";
                    break;
            }
        } while($opcao != 0);
    }


    private function getUserInput($prompt)
    {
        echo $prompt;
        return trim(fgets(STDIN));
    }

    private function displayItems($items, $label)
    {
        if (empty($items)) {
            echo "Nenhum $label encontrado.\n";
        } else {
            echo "$label:\n";
            foreach ($items as $item) {
                $this->displayItem($item, '');
            }
        }
    }

    public function exibirTitulo() {
        echo
        "\r---------------------------------------------------------------------
        \r                            IFome
        \r---------------------------------------------------------------------
        \r";
    }

    private function displayItem($item, $label)
    {
        echo "\n$label\n";
        foreach ($item as $key => $value) {
            if ($key === 'id') {
                echo "$key: $value\n";
            } else {
                echo "$key: $value\n";
            }
        }
        echo "----------------------------------------\n";
    }

    private function handleException(RequestException $e)
    {
        if ($e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            $reasonPhrase = $response->getReasonPhrase();

            echo "Erro na solicitação à API:\n";
            echo "Status Code: $statusCode\n";
            echo "Motivo: $reasonPhrase\n";

            $body = json_decode($response->getBody(), true);
            if ($body && isset($body['message'])) {
                echo "Detalhes: " . $body['message'] . "\n";
            }

        } else {
            echo "Erro na solicitação à API: " . $e->getMessage() . "\n";
        }
    }

}
$apiClient = new IFomeClient();
$apiClient->run();
