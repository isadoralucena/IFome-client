<?php

require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class IFomeApiClient
{
    private $client;

    public function __construct()
    {
        try {
            $this->client = new Client([
                'base_uri' => 'http://localhost:8000/api/',
            ]);
        } catch (Exception $e) {
            $this->handleApiConnectionError($e);
        }
    }
    private function handleApiConnectionError(Exception $e)
    {
        echo "Erro na conexão com a API: " . $e->getMessage() . "\n";
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

        if ((!is_numeric($data['quantidade_estoque'])) || $data['quantidade_estoque'] < 0) {
            $errors .= "Quantidade em estoque deve ser um número inteiro não negativo.\n";
        }

        if ((!is_numeric($data['valor'])) || $data['valor'] < 0) {
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

        if ((!is_numeric($data['quantidade_estoque'])) || $data['quantidade_estoque'] < 0) {
            $errors .= "Quantidade em estoque deve ser um número inteiro não negativo.\n";
        }

        if ((!is_numeric($data['valor'])) || $data['valor'] < 0) {
            $errors .= "Valor deve ser um número não negativo.\n";
        }

        return $errors;
    }

    public function getAlimentos()
    {
        try {
            $response = $this->client->get('alimentos');
            $alimentos = json_decode($response->getBody(), true);
            $this->displayItems($alimentos, 'Alimentos');
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function getAlimentoById($id)
    {
        try {
            $response = $this->client->get("alimentos/{$id}");
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $this->handleException($e);
            return null;
        }    
    }

    public function cadastrarAlimento()
    {
        echo "Informe os dados do novo Alimento:\n";
        $novoAlimento = [
            'nome' => $this->getUserInput("Nome: "),
            'composicao' => $this->getUserInput("Composição: "),
            'quantidade_estoque' => intval($this->getUserInput("Quantidade em estoque: ")),
            'valor' => floatval($this->getUserInput("Valor: ")),
        ];

        $validationErrors = $this->validateBebida($novoAlimento);

        if (!empty($validationErrors)) {
            echo "Erros de validação:\n";
            echo $validationErrors;
            return;
        }

        try {
            $response = $this->client->post('alimentos', [
                'json' => $novoAlimento,
            ]);
            echo "Alimento cadastrado com sucesso.\n";
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }


    public function cadastrarBebida()
    {
        echo "Informe os dados da nova Bebida:\n";
        $novaBebida = [
            'nome' => $this->getUserInput("Nome: "),
            'quantidade_estoque' => intval($this->getUserInput("Quantidade em estoque: ")),
            'valor' => floatval($this->getUserInput("Valor: ")),
        ];

        $validationErrors = $this->validateBebida($novaBebida);

        if (!empty($validationErrors)) {
            echo "Erros de validação:\n";
            echo $validationErrors;
            return;
        }

        try {
            $response = $this->client->post('bebidas', [
                'json' => $novaBebida,
            ]);
            echo "Bebida cadastrada com sucesso.\n";
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function editarAlimento()
    {
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

            $response = $this->client->put("alimentos/{$id}", [
                'json' => $alimentoEditado,
            ]);

            echo "Alimento editado com sucesso.\n";
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }


    public function excluirAlimento()
    {
        $id = intval($this->getUserInput("Digite o ID do Alimento a ser excluído: "));

        try {
            $response = $this->client->delete("alimentos/{$id}");
            echo "Alimento excluído com sucesso.\n";
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function getBebidas()
    {
        try {
            $response = $this->client->get('bebidas');
            $bebidas = json_decode($response->getBody(), true);
            $this->displayItems($bebidas, 'Bebidas');
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function getBebidaById($id)
    {
        try {
            $response = $this->client->get("bebidas/{$id}");
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $this->handleException($e);
            return null;
        }
    }

    public function editarBebida()
    {
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

            $response = $this->client->put("bebidas/{$id}", [
                'json' => $bebidaEditada,
            ]);

            echo "Bebida editada com sucesso:\n";
            $this->displayItem($bebidaEditada, '');
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function excluirBebida()
    {
        $id = intval($this->getUserInput("Digite o ID da Bebida a ser excluída: "));

        try {
            $response = $this->client->delete("bebidas/{$id}");
            echo "Bebida excluída com sucesso.\n";
        } catch (RequestException $e) {
            $this->handleException($e);
        }
    }

    public function showMenu()
    {
        echo "Escolha uma opção:\n";
        echo "1. Listar Alimentos\n";
        echo "2. Obter detalhes de um Alimento\n";
        echo "3. Cadastrar Alimento\n";
        echo "4. Editar Alimento\n";
        echo "5. Excluir Alimento\n";
        echo "6. Listar Bebidas\n";
        echo "7. Obter detalhes de uma Bebida\n";
        echo "8. Cadastrar Bebida\n";
        echo "9. Editar Bebida\n";
        echo "10. Excluir Bebida\n";
        echo "0. Sair\n";
    }

    public function run()
    {
        $opcao = -1;

        while ($opcao != 0) {
            $this->showMenu();
            echo "Digite a opção desejada: ";
            $opcao = intval(trim(fgets(STDIN)));

            switch ($opcao) {
                case 1:
                    $this->getAlimentos();
                    break;

                case 2:
                    $id = intval($this->getUserInput("Digite o ID do Alimento: "));
                    $this->getAlimentoById($id);
                    break;

                case 3:
                    $this->cadastrarAlimento();
                    break;

                case 4:
                    $this->editarAlimento();
                    break;

                case 5:
                    $this->excluirAlimento();
                    break;

                case 6:
                    $this->getBebidas();
                    break;

                case 7:
                    $id = intval($this->getUserInput("Digite o ID da Bebida: "));
                    $this->getBebidaById($id);
                    break;

                case 8:
                    $this->cadastrarBebida();
                    break;

                case 9:
                    $this->editarBebida();
                    break;

                case 0:
                    echo "Saindo...\n";
                    break;

                default:
                    echo "Opção inválida. Tente novamente.\n";
                    break;
            }
        }
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

$apiClient = new IFomeApiClient();
$apiClient->run();

