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
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;
use WapplerSystems\ZabbixClient\Service\CoreVersionService;


/**
 *
 */
#[MonitoringOperation('HasUpdate')]
class HasUpdate implements IOperation, SingletonInterface
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
            // Version is not maintained -> see outdated operation
            return new OperationResult(true, false);
        }

        // There is an update available
        $availableReleases = [];
        $latestRelease = $coreVersionService->getYoungestPatchRelease();
        $isCurrentVersionElts = $coreVersionService->isCurrentInstalledVersionElts();

        if ($coreVersionService->isPatchReleaseSuitableForUpdate($latestRelease)) {
            $availableReleases[] = $latestRelease;
        }

        if (!$versionMaintenanceWindow->isSupportedByCommunity() && $latestRelease->isElts()) {
            $latestCommunityDrivenRelease = $coreVersionService->getYoungestCommunityPatchRelease();
            if ($coreVersionService->isPatchReleaseSuitableForUpdate($latestCommunityDrivenRelease)) {
                $availableReleases[] = $latestCommunityDrivenRelease;
            }
        }
        if ($availableReleases === []) {
            // Everything is fine, working with the latest version
            return new OperationResult(true, false);
        }

        foreach ($availableReleases as $availableRelease) {
            if (($availableRelease->isElts() && $isCurrentVersionElts) || (!$availableRelease->isElts() && !$isCurrentVersionElts) ) {
                return new OperationResult(true, true);
            }
        }

        return new OperationResult(false, false);
    }
}
