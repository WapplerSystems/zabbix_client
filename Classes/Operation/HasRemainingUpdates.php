<?php

namespace WapplerSystems\ZabbixClient\Operation;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Install\Service\UpgradeWizardsService;
use WapplerSystems\ZabbixClient\OperationResult;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Core\Bootstrap;
/**
 *
 */
class HasRemainingUpdates implements IOperation, SingletonInterface
{

    /**
     *
     * @param array $parameter None
     * @return OperationResult
     */
    public function execute($parameter = [])
    {
        $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);

        if (version_compare($typo3Version->getVersion(), '9.0.0', '<')) {

        if (version_compare($typo3Version->getVersion(), '9.0.0', '<')) {

            \TYPO3\CMS\Core\Core\Bootstrap::getInstance()
                ->ensureClassLoadingInformationExists()
                ->loadTypo3LoadedExtAndExtLocalconf(false)
                ->defineLoggingAndExceptionConstants()
                ->unsetReservedGlobalVariables()
                ->initializeTypo3DbGlobal()
                ->loadBaseTca(false)
                ->loadExtTables(false);

            if (!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'])) {
                $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] = [];
            }

            $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] = array_merge($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'],
                [
                    'databaseCharsetUpdate' => \TYPO3\CMS\Install\Updates\DatabaseCharsetUpdate::class,
                    'initialUpdateDatabaseSchema' => \TYPO3\CMS\Install\Updates\InitialDatabaseSchemaUpdate::class,
                    'finalUpdateDatabaseSchema' => \TYPO3\CMS\Install\Updates\FinalDatabaseSchemaUpdate::class,
                ]
            );
            $typo3Version = GeneralUtility::makeInstance(Typo3Version::class);

            $versionAsInt = VersionNumberUtility::convertVersionNumberToInteger($typo3Version->getVersion());
            $registry = GeneralUtility::makeInstance(Registry::class);

            try {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] as $identifier => $className) {
                    $updateObject = GeneralUtility::makeInstance($className, $identifier, $versionAsInt, null, $this);
                    $markedDoneInRegistry = $registry->get('installUpdate', $className, false);
                    if (!$markedDoneInRegistry && $updateObject->shouldRenderWizard()) {
                        // at least one wizard was found
                        return new OperationResult(true, true);
                    }
                }

            } catch (StatementException $exception) {
                return new OperationResult(false, 'error 4325534583');
            }

            return new OperationResult(true, false);
        }

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
