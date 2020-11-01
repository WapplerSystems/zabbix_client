<?php
declare(strict_types=1);

namespace WapplerSystems\ZabbixClient\Middleware;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */


use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Extbase\Mvc\ResponseInterface;
use WapplerSystems\ZabbixClient\ManagerFactory;
use WapplerSystems\ZabbixClient\Response\JsonResponse;
use WapplerSystems\ZabbixClient\Exception\InvalidOperationException;
use WapplerSystems\ZabbixClient\Authorization\IpAuthorizationProvider;
use WapplerSystems\ZabbixClient\Authentication\KeyAuthenticationProvider;


class Eid
{

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     */
    public function processRequest(ServerRequestInterface $request, \TYPO3\CMS\Core\Http\Response $response)
    {
        $ip = GeneralUtility::getIndpEnv('REMOTE_ADDR');
        $ipAuthorizationProvider = new IpAuthorizationProvider();
        if (!$ipAuthorizationProvider->isAuthorized($ip)) {
            return $response->withStatus(403, 'Not allowed');
        }

        $key = $request->getParsedBody()['key'] ?? $request->getQueryParams()['key'] ?? null;
        $keyAuthenticationProvider = new KeyAuthenticationProvider();
        if (!$keyAuthenticationProvider->hasValidKey($key)) {

            /** @var $logger Logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->error('API key wrong', ['ip' => $_SERVER['REMOTE_ADDR']]);


            return $response->withStatus(403, 'API key wrong');
        }

        $operation = $request->getParsedBody()['operation'] ?? $request->getQueryParams()['operation'] ?? null;
        $params = array_merge($request->getParsedBody() ?? [], $request->getQueryParams() ?? []);

        $managerFactory = ManagerFactory::getInstance();

        if ($operation !== null && $operation !== '') {
            $operationManager = $managerFactory->getOperationManager();
            try {
                $result = $operationManager->executeOperation($operation, $params);
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
