<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Tests\Functional\Middleware;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\Middleware\ZabbixClient;
use WapplerSystems\ZabbixClient\OperationManager;

class ZabbixClientTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/zabbix_client',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'zabbix_client' => [
                'apiKey' => 'test-api-key-12345',
                'allowedIps' => '*',
            ],
        ],
    ];

    private function createHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public bool $called = false;
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->called = true;
                return new Response();
            }
        };
    }

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    private function createRequest(string $path, array $queryParams = []): ServerRequestInterface
    {
        $uri = new Uri('https://example.com' . $path);
        $request = new ServerRequest($uri, 'GET');
        return $request->withQueryParams($queryParams)->withUri($uri);
    }

    #[Test]
    public function nonZabbixRequestPassesThrough(): void
    {
        $operationManager = $this->get(OperationManager::class);
        $middleware = new ZabbixClient($operationManager);

        $handler = $this->createHandler();
        $request = $this->createRequest('/some/other/path');

        $middleware->process($request, $handler);

        self::assertTrue($handler->called);
    }

    #[Test]
    public function zabbixRequestIsIntercepted(): void
    {
        $operationManager = $this->get(OperationManager::class);
        $middleware = new ZabbixClient($operationManager);

        $handler = $this->createHandler();
        $request = $this->createRequest('/zabbixclient/', [
            'key' => 'test-api-key-12345',
            'operation' => 'GetPHPVersion',
        ]);

        $middleware->process($request, $handler);

        self::assertFalse($handler->called);
    }

    #[Test]
    public function invalidApiKeyReturns403(): void
    {
        $operationManager = $this->get(OperationManager::class);
        $middleware = new ZabbixClient($operationManager);

        $handler = $this->createHandler();
        $request = $this->createRequest('/zabbixclient/', [
            'key' => 'wrong-key',
            'operation' => 'GetPHPVersion',
        ]);

        $response = $middleware->process($request, $handler);

        self::assertSame(403, $response->getStatusCode());
    }

    #[Test]
    public function missingOperationReturns404(): void
    {
        $operationManager = $this->get(OperationManager::class);
        $middleware = new ZabbixClient($operationManager);

        $handler = $this->createHandler();
        $request = $this->createRequest('/zabbixclient/', [
            'key' => 'test-api-key-12345',
        ]);

        $response = $middleware->process($request, $handler);

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function validRequestReturnsJsonResponse(): void
    {
        $operationManager = $this->get(OperationManager::class);
        $middleware = new ZabbixClient($operationManager);

        $handler = $this->createHandler();
        $request = $this->createRequest('/zabbixclient/', [
            'key' => 'test-api-key-12345',
            'operation' => 'GetPHPVersion',
        ]);

        $response = $middleware->process($request, $handler);

        self::assertSame(200, $response->getStatusCode());
        $body = json_decode((string)$response->getBody(), true);
        self::assertTrue($body['status']);
        self::assertNotEmpty($body['value']);
    }

    #[Test]
    public function unknownOperationReturns404(): void
    {
        $operationManager = $this->get(OperationManager::class);
        $middleware = new ZabbixClient($operationManager);

        $handler = $this->createHandler();
        $request = $this->createRequest('/zabbixclient/', [
            'key' => 'test-api-key-12345',
            'operation' => 'NonExistentOperation',
        ]);

        $response = $middleware->process($request, $handler);

        self::assertContains($response->getStatusCode(), [404, 500]);
    }
}
