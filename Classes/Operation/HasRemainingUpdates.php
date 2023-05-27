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
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\OperationResult;

/**
 *
 */
#[MonitoringOperation('HasRemainingUpdates')]
class HasRemainingUpdates implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {

        $upgradeWizardsService = GeneralUtility::makeInstance(UpgradeWizardsService::class);
        $incompleteWizards = $upgradeWizardsService->getUpgradeWizardsList();
        $incompleteWizards = array_filter(
            $incompleteWizards,
            function ($wizard) {
                return $wizard['shouldRenderWizard'];
            }
        );
        return new OperationResult(true, count($incompleteWizards) > 0);
    }

}
