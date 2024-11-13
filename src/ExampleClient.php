<?php

declare(strict_types=1);

namespace Kokov1ch\DromExampleClient;

use Kokov1ch\DromExampleClient\Dto\Comment;
use Kokov1ch\DromExampleClient\Exception\ExampleClientException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

final readonly class ExampleClient
{
    private const string BASE_URL = 'https://example.com';

    public function __construct(
        private ClientInterface $client,
        private StreamFactoryInterface $streamFactory,
        private RequestFactoryInterface $requestFactory
    ) {
    }

    /**
     * @throws ExampleClientException
     */
    public function getComments(): array
    {
        try {
            $response = $this->client
                ->sendRequest(request: $this->requestFactory->createRequest(method: 'GET', uri: self::BASE_URL.'/comments'));
            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new ExampleClientException(
                    message: "Failed to get comments. Response: {$response->getBody()->getContents()}",
                    code: $statusCode
                );
            }

            return $this->deserialize($response->getBody()->getContents());
        } catch (\Throwable $exception) {
            match (true) {
                $exception instanceof ExampleClientException => throw $exception,
                $exception instanceof \JsonException => throw new ExampleClientException(
                    message: "JSON decoding error: " . $exception->getMessage(),
                    code: 400
                ),
                default => throw new ExampleClientException(
                    message: $exception->getMessage(),
                    code: $exception->getCode(),
                    previous: $exception
                )
            };
        }
    }

    /**
     * @throws ExampleClientException
     */
    public function addComment(string $name, string $text): string
    {
        try {
            $response = $this->client->sendRequest(
                request: $this->requestFactory->createRequest(
                    method: 'POST',
                    uri: self::BASE_URL.'/comment'
                )
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode(['name' => $name, 'text' => $text])))
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new ExampleClientException(
                    message: "Failed to add comment. Response: {$response->getBody()->getContents()}",
                    code: $statusCode
                );
            }

            return $response->getBody()->getContents();
        }
        catch (\Throwable $exception)
        {
            match (true) {
                $exception instanceof ExampleClientException => throw $exception,
                default => throw new ExampleClientException(
                    message: $exception->getMessage(),
                    code: $exception->getCode(),
                    previous: $exception
                )
            };
        }
    }

    /**
     * @throws ExampleClientException
     */
    public function updateComment(int $id, ?string $name = null, ?string $text = null): string
    {
        try{
            $body = array_filter([
                'name' => $name,
                'text' => $text,
            ], fn($value): bool => null !== $value);

            $response = $this->client->sendRequest(
                request: $this->requestFactory->createRequest(
                    method: 'PUT',
                    uri: self::BASE_URL.'/comments'.$id
                )
                    ->withHeader('Content-Type', 'application/json')
                    ->withBody($this->streamFactory->createStream(json_encode($body)))
            );

            $statusCode = $response->getStatusCode();

            if ($statusCode < 200 || $statusCode >= 300) {
                throw new ExampleClientException(
                    message: "Failed to update comment. Response: {$response->getBody()->getContents()}",
                    code: $statusCode
                );
            }

            return $response->getBody()->getContents();
        }
        catch (\Throwable $exception)
        {
            match (true) {
                $exception instanceof ExampleClientException => throw $exception,
                default => throw new ExampleClientException(
                    message: $exception->getMessage(),
                    code: $exception->getCode(),
                    previous: $exception
                )
            };
        }
    }


    /**
     * @return array<Comment>
     * @throws ExampleClientException
     * @throws \JsonException
     */
    private function deserialize(string $content): array
    {
        $data = json_decode(
            json: $content,
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );

        $comments = [];

        foreach ($data as $comment) {
            if (!isset($comment['id'], $comment['name'], $comment['text'])) {
                throw new ExampleClientException(
                    message: "Comment data missing required fields: " . json_encode($comment),
                    code: 422
                );
            }

            $comments[] = new Comment(
                id: $comment['id'],
                name: $comment['name'],
                text: $comment['text']
            );
        }

        return $comments;
    }
}
