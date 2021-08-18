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
use WapplerSystems\ZabbixClient\Service\CoreVersionService;


/**
 *
 */
class HasOutdatedVersion implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {

        /** @var CoreVersionService $coreVersionService */
        $coreVersionService = GeneralUtility::makeInstance(CoreVersionService::class);

        // No updates for development versions
        if (!$coreVersionService->isInstalledVersionAReleasedVersion()) {
            return new OperationResult(true, false);
        }

        try {
            $versionMaintenanceWindow = $coreVersionService->getMaintenanceWindow();
        } catch (RemoteFetchException $remoteFetchException) {
            return new OperationResult(false, false);
        }

        if (!$versionMaintenanceWindow->isSupportedByCommunity() && !$versionMaintenanceWindow->isSupportedByElts()) {
            return new OperationResult(true, true);
        }
        return new OperationResult(true, false);
    }
}
