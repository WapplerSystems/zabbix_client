<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Service\UpgradeWizardsService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
    public function execute(array $parameter = []): OperationResult
    {
        $upgradeWizardsService = GeneralUtility::makeInstance(UpgradeWizardsService::class);
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $hasRemaining = false;
        foreach ($upgradeWizardsService->getUpgradeWizardIdentifiers() as $identifier) {
            try {
                if ($upgradeWizardsService->isWizardDone($identifier)) {
                    continue;
                }
                $info = $upgradeWizardsService->getWizardInformationByIdentifier($identifier);
                if (!empty($info['shouldRenderWizard'])) {
                    $hasRemaining = true;
                }
            } catch (\Throwable $e) {
                // A single broken upgrade wizard must not break the whole monitoring call.
                // Report remaining=true so the broken state surfaces in Zabbix.
                $logger->warning('Upgrade wizard "{identifier}" check failed: {message}', [
                    'identifier' => $identifier,
                    'message' => $e->getMessage(),
                    'exception' => $e,
                ]);
                $hasRemaining = true;
            }
        }
        return new OperationResult(true, $hasRemaining);
    }

}