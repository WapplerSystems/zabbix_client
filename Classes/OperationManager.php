<?php

namespace WapplerSystems\ZabbixClient;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use Symfony\Component\DependencyInjection\ServiceLocator;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Operation\IOperation;


class OperationManager
{

    public function __construct(
        private readonly ServiceLocator $operations
    ) {
    }

    /**
     * Whether a registered upgrade wizard exists for the given identifier
     */
    public function hasOperation(string $identifier): bool
    {
        return $this->operations->has($identifier) || $this->getLegacyOperationClassName($identifier) !== null;
    }

    /**
     * Get a registered operation as instance
     * @param $identifier
     * @return IOperation
     * @throws \UnexpectedValueException
     */
    public function getOperation($identifier) : IOperation
    {
        if (!$this->hasOperation($identifier)) {
            throw new \UnexpectedValueException('Monitoring operation with identifier ' . $identifier . ' is not registered.', 1685139937);
        }

        return $this->getLegacyOperation($identifier) ?? $this->operations->get($identifier);
    }

    /**
     * Execute an Operation by key with optional parameters
     *
     * @param string $identifier
     * @param array|null $parameter
     * @return OperationResult
     * @throws \UnexpectedValueException
     */
    public function executeOperation($identifier, $parameter = []) : OperationResult
    {
        return $this->getOperation($identifier)->execute($parameter);
    }


    private function getLegacyOperationClassName(string $identifier): ?string
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zabbix_client']['operations'][$identifier])
            && class_exists($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zabbix_client']['operations'][$identifier])
        ) {
            return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['zabbix_client']['operations'][$identifier];
        }
        return null;
    }

    private function getLegacyOperation(string $identifier): ?IOperation
    {
        $className = $this->getLegacyOperationClassName($identifier);
        if ($className === null) {
            return null;
        }

        $instance = GeneralUtility::makeInstance($className);
        return $instance instanceof IOperation ? $instance : null;
    }


}
