# IFome - cliente
Cliente interativo no terminal que consome a API [IFome](https://github.com/isadoralucena/IFome) usando a biblioteca Guzzle e PHP

## Dependências

Instale a biblioteca Guzzle

```
composer install
```

No construtor, na `base_uri`, insira a url que a API está rodando

```php
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
```

Execute o arquivo

```
php IFomeApiCliente.php
```