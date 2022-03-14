<?php

namespace WapplerSystems\ZabbixClient;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Operation\IOperation;

/**
 * Singleton factory as a dependency injection container
 *
 */
class ManagerFactory
{
    /**
     * @var ManagerFactory
     */
    protected static $instance;

    /**
     * @var OperationManager
     */
    protected $operationManager;

    /**
     * @var array
     */
    private $extConf;

    /**
     * Constructor
     */
    public function __construct()
    {
        if (version_compare(TYPO3_version, '9.0.0', '>=')) {
            $this->extConf = GeneralUtility::makeInstance(
                ExtensionConfiguration::class
            )->get('zabbix_client');
        } else {
            $this->extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['zabbix_client']);
        }
    }

    /**
     * @static
     * @return ManagerFactory
     */
    public static function getInstance(): ManagerFactory
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }


    /**
     * @return OperationManager
     */
    public function getOperationManager(): OperationManager
    {
        if ($this->operationManager === null) {
            $this->operationManager = new OperationManager();

            if (is_array($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zabbix_client']['operations'])) {
                foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zabbix_client']['operations'] as $key => $operationRef) {
                    if (is_string($operationRef)) {
                        $operation = GeneralUtility::makeInstance($operationRef);
                    } elseif ($operationRef instanceof IOperation) {
                        $operation = $operationRef;
                    }
                    // TODO log error if some strange value is registered

                    $this->operationManager->registerOperation($key, $operation);
                }
            }
        }

        return $this->operationManager;
    }


    /**
     * Destroy the factory instance
     */
    public static function destroy()
    {
        self::$instance = null;
    }
}
