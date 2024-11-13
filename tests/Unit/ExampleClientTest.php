<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use Kokov1ch\DromExampleClient\Dto\Comment;
use Kokov1ch\DromExampleClient\ExampleClient;
use Kokov1ch\DromExampleClient\Exception\ExampleClientException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

final class ExampleClientTest extends TestCase
{
    private readonly ExampleClient $exampleClient;
    private readonly ClientInterface $client;
    private readonly ResponseInterface $response;
    private readonly StreamInterface $stream;

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $this->client = $this->createMock(ClientInterface::class);
        $request = $this->createStub(originalClassName: RequestInterface::class);
        $this->response = $this->createStub(originalClassName: ResponseInterface::class);
        $this->stream = $this->createStub(originalClassName:StreamInterface::class);
        $request->method('withBody')->willReturnSelf();
        $request->method('withHeader')->willReturnSelf();


        $requestFactory = $this->createMock(RequestFactoryInterface::class);
        $requestFactory->method('createRequest')->willReturn($request);

        $streamFactory = $this->createMock(StreamFactoryInterface::class);
        $streamFactory->method('createStream')->willReturn($this->stream);

        $this->exampleClient = new ExampleClient(
            $this->client,
            $streamFactory,
            $requestFactory
        );
    }


    #[DataProvider('getCommentsProvider')]
    public function testGetComments(
        array $mockResponse,
        array $comments,
        ?int $errorCode = null,
        ?string $exception = null
    ): void
    {
        $this->setupResponseMock($mockResponse);

        if ($exception) {
            $this->expectException(ExampleClientException::class);
            $this->expectExceptionMessage($exception);
            $this->expectExceptionCode($errorCode);
        }

        $result = $this->exampleClient->getComments();

        if (!$exception) {
            $this->assertEquals($comments, $result);
        }

    }

    #[DataProvider('clientInterfaceExceptionProvider')]
    public function testClientInterfaceExceptionOn(
        string $method,
        ?string $name = null,
        ?string $text = null,
        ?int $id = null
    ): void
    {
        $clientException = $this->createMock(ClientExceptionInterface::class);

        $this->client->method('sendRequest')->willThrowException($clientException);

        try {
            match ($method)
            {
                'getComments' => $this->exampleClient->getComments(),
                'addComment' => $this->exampleClient->addComment($name, $text),
                'updateComment' => $this->exampleClient->updateComment($id, $name, $text),
            };
            $this->expectException(ExampleClientException::class);

        } catch (ExampleClientException $exception) {
            $this->assertInstanceOf(ClientExceptionInterface::class, $exception->getPrevious());
        }
    }

    #[DataProvider('addCommentProvider')]
    public function testAddComment(array $mockResponse, string $name, string $text, ?string $exception = null, ?int $errorCode = null): void
    {
        $this->setupResponseMock($mockResponse);

        if ($exception) {
            $this->expectException(ExampleClientException::class);
            $this->expectExceptionMessage($exception);
            $this->expectExceptionCode($errorCode);
        }

        $this->exampleClient->addComment($name, $text);
        $this->assertTrue(true);
    }

    #[DataProvider('updateCommentProvider')]
    public function testUpdateComment(
        int $id,
        array $mockResponse,
        ?string $name = null,
        ?string $text = null,
        ?string $exception = null,
        ?int $errorCode = null
    ): void
    {
        $this->setupResponseMock($mockResponse);

        if ($exception) {
            $this->expectException(ExampleClientException::class);
            $this->expectExceptionMessage($exception);
            $this->expectExceptionCode($errorCode);
        }

        $this->exampleClient->updateComment($id, $name, $text);
        $this->assertTrue(true);
    }

    public static function updateCommentProvider(): iterable
    {
        yield 'Success' => [
            'id' => 1,
            'name' => 'Uli Roth',
            'text' => 'The sails of charon',
            'mockResponse' => [
                'statusCode' => 200,
                'body' => '{"message": "Comment updated successfully"}'
            ],
        ];

        yield 'Success no fields' => [
            'id' => 2,
            'mockResponse' => [
                'statusCode' => 200,
                'body' => '{"message": "Comment updated successfully"}'
            ],
        ];

        yield 'Internal error' => [
            'id' => 1,
            'name' => 'James Hetfield',
            'text' => 'Master of puppets',
            'mockResponse' => [
                'statusCode' => 500,
                'body' => 'Internal Server Error'
            ],
            'exception' => 'Failed to update comment. Response: Internal Server Error',
            'errorCode' => 500
        ];

        yield 'Not found 404' => [
            'id' => 1,
            'name' => 'Brian May',
            'text' => 'cockroach',
            'mockResponse' => [
                'statusCode' => 404,
                'body' => 'Not Found'
            ],
            'exception' => 'Failed to update comment. Response: Not Found',
            'errorCode' => 404
        ];
    }

    public static function clientInterfaceExceptionProvider(): iterable
    {
        yield 'getComments exception' => ['getComments'];
        yield 'addComment exception' => ['addComment', 'Uli', 'Blind man'];
        yield 'updateComment exception' => ['addComment', 'John', 'Sucking your own blood', 1

        ];
    }
    public static function addCommentProvider(): iterable
    {
        yield 'Success' => [
            'mockResponse' => [
                'statusCode' => 200,
                'body' => '{"message": "Comment added successfully"}'
            ],
            'name' => 'Fedor',
            'text' => 'Fet',
        ];

        yield 'Internal error' => [
            'mockResponse' => [
                'statusCode' => 500,
                'body' => 'Internal Server Error'
            ],
            'name' => 'Mustapha',
            'text' => 'Mochamut dei ya low eshelei',
            'errorCode' => 500,
            'exception' => 'Failed to add comment. Response: Internal Server Error',
        ];
    }

    public static function getCommentsProvider(): iterable
    {
        yield 'Success n comments' => [
            'mockResponse' => [
                'statusCode' => 200,
                'body' => '[{"id": 1, "name": "Fedor", "text": "Comment1"}, {"id": 2, "name": "Fet", "text": "Comment2"}]',
            ],
            'comments' => [
                new Comment(1, 'Fedor', 'Comment1'),
                new Comment(2, 'Fet', 'Comment2'),
            ],
        ];

        yield 'Success 1 comment' => [
            'mockResponse' => [
                'statusCode' => 200,
                'body' => '[{"id": 1, "name": "Mustapha", "text": "Ibrahim"}]',
            ],
            'comments' => [
                new Comment(1, 'Mustapha', 'Ibrahim'),
            ],
        ];

        yield 'Internal error' => [
            'mockResponse' => [
                'statusCode' => 500,
                'body' => 'Internal Server Error',
            ],
            'comments' => [],
            'exception' => 'Failed to get comments. Response: Internal Server Error',
            'errorCode' => 500,
        ];

        yield 'No required field' => [
            'mockResponse' => [
                'statusCode' => 200,
                'body' => '[{"id": 1, "name": "User1"}]',
            ],
            'comments' => [],
            'exception' => 'Comment data missing required fields: {"id":1,"name":"User1"}',
            'errorCode' => 422,
        ];

        yield 'Json decoding error' => [
            'mockResponse' => [
                'statusCode' => 200,
                'body' => '{"Mustapha}',
            ],
            'comments' => [],
            'exception' => 'JSON decoding error: Control character error, possibly incorrectly encoded',
            'errorCode' => 400,
            ];
    }

    private function setupResponseMock(array $mockResponse): void
    {
        $this->stream->method('getContents')->willReturn($mockResponse['body']);
        $this->response->method('getStatusCode')->willReturn($mockResponse['statusCode']);
        $this->response->method('getBody')->willReturn($this->stream);
        $this->client->method('sendRequest')->willReturn($this->response);
    }

}
