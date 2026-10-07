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
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use WapplerSystems\ZabbixClient\Middleware\ZabbixClient;
use WapplerSystems\ZabbixClient\OperationManager;

/**
 * Default installation state: no API key configured, all IPs allowed.
 * The endpoint must not answer any operation in this state.
 */
class ZabbixClientWithoutApiKeyTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'wapplersystems/zabbix_client',
    ];

    protected array $configurationToUseInTestInstance = [
        'EXTENSIONS' => [
            'zabbix_client' => [
                'apiKey' => '',
                'allowedIps' => '*',
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
    }

    private function process(array $queryParams): ResponseInterface
    {
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response();
            }
        };
        $uri = new Uri('https://example.com/zabbixclient/');
        $request = (new ServerRequest($uri, 'GET'))->withQueryParams($queryParams);

        return (new ZabbixClient($this->get(OperationManager::class)))->process($request, $handler);
    }

    #[Test]
    public function requestWithoutKeyIsDenied(): void
    {
        self::assertSame(403, $this->process(['operation' => 'GetTYPO3Version'])->getStatusCode());
    }

    #[Test]
    public function requestWithEmptyKeyIsDenied(): void
    {
        self::assertSame(403, $this->process(['key' => '', 'operation' => 'GetTYPO3Version'])->getStatusCode());
    }

    #[Test]
    public function recordsAreNotDisclosed(): void
    {
        $response = $this->process(['operation' => 'GetRecords', 'table' => 'be_users', 'field' => 'admin', 'value' => '1']);

        self::assertSame(403, $response->getStatusCode());
        self::assertSame('', (string)$response->getBody());
    }
}
