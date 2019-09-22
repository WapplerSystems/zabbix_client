<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Middleware;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */


use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Authentication\KeyAuthenticationProvider;
use WapplerSystems\ZabbixClient\ManagerFactory;


class ZabbixClient implements MiddlewareInterface
{
    /**
     * Calls the "unavailableAction" of the error controller if the system is in maintenance mode.
     * This only applies if the REMOTE_ADDR does not match the devIpMask
     *
     * @param ServerRequestInterface $request
     * @param RequestHandlerInterface $handler
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        /** @var \Psr\Http\Message\UriInterface $requestedUri */
        $requestedUri = $request->getUri();
        if (strpos($requestedUri->getPath(), '/zabbixclient/') === 0) {
            return $this->processRequest($request);
        }

        return $handler->handle($request);
    }

    private function processRequest(ServerRequestInterface $request)
    {

        $key = $request->getParsedBody()['key'] ?? $request->getQueryParams()['key'] ?? null;


        $keyAuthenticationProvider = new KeyAuthenticationProvider();
        if (!$keyAuthenticationProvider->hasValidKey($key)) {
            /** @var Response $response */
            $response = GeneralUtility::makeInstance(Response::class);

            /** @var $logger Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->error('API key wrong', ['ip' => $_SERVER['REMOTE_ADDR']]);

            return $response->withStatus(403, 'API key wrong');
        }

        $operation = $request->getParsedBody()['operation'] ?? $request->getQueryParams()['operation'] ?? null;
        $params = $request->getParsedBody() ?? $request->getQueryParams();


        $managerFactory = ManagerFactory::getInstance();

        if ($operation !== null && $operation !== '') {
            $operationManager = $managerFactory->getOperationManager();
            $result = $operationManager->executeOperation($operation, $params);
        }

        if ($result !== null) {
            return new JsonResponse($result->toArray());
        }

        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);
        return $response->withStatus(404, 'operation or service parameter not set');
    }
}
