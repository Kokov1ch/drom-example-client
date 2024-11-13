## Example Client
Этот проект реализует клиент, совместимый с интерфейсами PSR-17 и PSR-7 для абстрактного сервиса комментариев example.com. Библиотека предоставляет набор PHP-классов для взаимодействия с сервисом через HTTP-запросы.

## Установка

### Локальный запуск с Docker

#### 1. Разверните локальное окружение:
```bash
make create
```

#### 2. Запустите тесты:
```bash
make test
```


### Установка как библиотеки Сomposer

#### 1. Убедитесь, что в вашем composer.json указана следующая настройка:

```"minimum-stability": "stable"```

#### 2. Установите библиотеку с помощью Packagist:
```bash
composer require kokov1ch/example-client
```
Если вы хотите использовать локальную версию библиотеки, добавьте её как символическую ссылку. Пример настройки composer.json:
```
...
  "require": {
    "php": ">=8.3",
    "kokov1ch/example-client": "@dev"
  },
  "repositories": [
    {
      "type": "path",
      "url": "vendor-fork/example-client",
      "options": {
        "symlink": true
      }
    }
  ]
  ...
```
## Примеры использования

### Без использования фреймворка 

```php
try {
    $httpClient = new Client();
    $httpFactory = new HttpFactory();
    
    $exampleClient = new ExampleClient(
        client: $httpClient,
        streamFactory: $httpFactory,
        requestFactory: $httpFactory
    );

    $comments = $exampleClient->getComments();
    echo "Список комментариев:\n";
    foreach ($comments as $comment) {
        echo "ID: {$comment->id}, Name: {$comment->name}, Text: {$comment->text}\n";
    }

    $response = $exampleClient->addComment('John Doe', 'This is a test comment.');
    echo "Добавлен комментарий. Ответ сервера: $response\n";

    $updateResponse = $exampleClient->updateComment(1, name: 'Jane Doe', text: 'Updated comment text.');
    echo "Комментарий обновлен. Ответ сервера: $updateResponse\n";

} catch (ExampleClientException $e) {
    echo "Произошла ошибка при работе с клиентом: {$e->getMessage()}\n";
}
```
### С использованием Symfony

#### **`services.yml`**
```yaml
services:
  
  GuzzleHttp\Client: ~
  GuzzleHttp\Psr7\HttpFactory: ~

  Kokov1ch\DromExampleClient\ExampleClient:
    arguments:
      $client: '@GuzzleHttp\Client'
      $streamFactory: '@GuzzleHttp\Psr7\HttpFactory'
      $requestFactory: '@GuzzleHttp\Psr7\HttpFactory'

  App\Service\CommentService:
    arguments:
      $client: '@Kokov1ch\DromExampleClient\ExampleClient'
```
#### **`CommentService.php`**
```php
<?php

declare(strict_types=1);

namespace App\Service;

use Kokov1ch\DromExampleClient\Dto\Comment;
use Kokov1ch\DromExampleClient\ExampleClient;
use Kokov1ch\DromExampleClient\Exception\ExampleClientException;

final readonly class CommentService
{
    public function __construct(private ExampleClient $client, )
    {}

    /**
     * Получить список комментариев.
     *
     * @return array<Comment>
     * @throws ExampleClientException
     */
    public function getComments(): array
    {
        return $this->client->getComments();
    }

    /**
     * Добавить новый комментарий.
     *
     * @throws ExampleClientException
     */
    public function addComment(string $name, string $text): string
    {
        return $this->client->addComment($name, $text);
    }

    /**
     * Обновить существующий комментарий.
     *
     * @throws ExampleClientException
     */
    public function updateComment(int $id, ?string $name = null, ?string $text = null): string
    {
        return $this->client->updateComment($id, $name, $text);
    }
}
```

