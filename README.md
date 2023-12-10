# IFome - cliente
Cliente interativo no terminal que consome a API [IFome](https://github.com/isadoralucena/IFome) usando a biblioteca Guzzle e PHP

## Dependências

Instale a biblioteca Guzzle

```
composer install
```

No construtor, na `base_uri`, insira a url que a [API IFome](https://github.com/isadoralucena/IFome) está rodando

```php
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
```

Execute o arquivo

```
php IFomeApiCliente.php
```
