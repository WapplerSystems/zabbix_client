<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\Exception\RemoteFetchException;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 *
 */
class HasSecurityUpdate implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {

        /** @var \TYPO3\CMS\Install\Service\CoreVersionService $coreVersionService */
        $coreVersionService = GeneralUtility::makeInstance(\TYPO3\CMS\Install\Service\CoreVersionService::class);

        try {
            return new OperationResult(true, $coreVersionService->isUpdateSecurityRelevant());
        } catch (RemoteFetchException $e) {
        }

        return new OperationResult(false, false);
    }
}
