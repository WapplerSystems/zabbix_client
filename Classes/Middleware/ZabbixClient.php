<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Middleware;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */


use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\Utility\DebugUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use WapplerSystems\ZabbixClient\Exception\InvalidOperationException;
use WapplerSystems\ZabbixClient\Authorization\IpAuthorizationProvider;
use WapplerSystems\ZabbixClient\Authentication\KeyAuthenticationProvider;
use WapplerSystems\ZabbixClient\OperationManager;


class ZabbixClient implements MiddlewareInterface
{

    public function __construct(
        private readonly OperationManager $operationManager
    ) {

    }

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

        $requestedUri = $request->getUri();
        if (str_starts_with($requestedUri->getPath(), '/zabbixclient/')) {
            return $this->processRequest($request);
        }

        return $handler->handle($request);
    }

    private function processRequest(ServerRequestInterface $request)
    {
        /** @var Response $response */
        $response = GeneralUtility::makeInstance(Response::class);

        $ip = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        $ipAuthorizationProvider = new IpAuthorizationProvider();
        if (!$ipAuthorizationProvider->isAuthorized($ip)) {
            return $response->withStatus(403, 'Not allowed');
        }

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
        $params = array_merge($request->getParsedBody() ?? [], $request->getQueryParams() ?? []);


        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest())->withAttribute(
            'applicationType',
            SystemEnvironmentBuilder::REQUESTTYPE_BE
        )->withAttribute('frontend.typoscript',new FrontendTypoScript(new RootNode(),[]));

        if ($operation !== null && $operation !== '') {
            try {
                $result = $this->operationManager->executeOperation($operation, $params);
            } catch (InvalidOperationException $ex){
                return $response->withStatus(404,  $ex->getMessage());
            } catch (\Exception $ex) {
                return $response->withStatus(500,  substr(strrchr(get_class($ex), "\\"), 1) . ': '. $ex->getMessage());
            }
        }

        if ($result !== null) {
            return new JsonResponse($result->toArray());
        }

        return $response->withStatus(404, 'operation or service parameter not set');
    }
}
