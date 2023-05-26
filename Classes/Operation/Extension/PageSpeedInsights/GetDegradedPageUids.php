<?php

namespace WapplerSystems\ZabbixClient\Operation\Extension\PageSpeedInsights;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use WapplerSystems\ZabbixClient\Attribute\MonitoringOperation;
use WapplerSystems\ZabbixClient\Exception\InvalidArgumentException;
use WapplerSystems\ZabbixClient\Operation\IOperation;
use WapplerSystems\ZabbixClient\OperationResult;


/**
 * An Operation that returns the version of an installed extension
 *
 */
#[MonitoringOperation('GetDegradedPageUids')]
class GetDegradedPageUids implements IOperation, SingletonInterface
{
    /**
     *
     * @param array $parameter None
     * @return OperationResult The extension version
     */
    public function execute($parameter = [])
    {
        if (!ExtensionManagementUtility::isLoaded('pagespeedinsights')) {
            return new OperationResult(false, '');
        }


        if (!isset($parameter['strategy']) || $parameter['strategy'] === '') {
            throw new InvalidArgumentException('no strategy set');
        }

        if (!isset($parameter['field']) || $parameter['field'] === '') {
            throw new InvalidArgumentException('no field set');
        }

        $uids = self::getDegradedPageIds($parameter['field'],$parameter['strategy']);

        return new OperationResult(false, implode(',',$uids));
    }


    public static function getDegradedPageIds($field, $strategy):array {

        $pageIds = [];

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $conditions = [
            $queryBuilder->expr()->eq('tx_pagespeedinsights_check', 1)
        ];
        $pages = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(...$conditions)
            ->executeQuery()
            ->fetchAllNumeric();

        foreach ($pages as $page) {
            $trend = self::getTrendOfPage($page['uid'], $strategy, $field);
            if ($trend < 0) {
                $pageIds[] = $page['uid'];
            }
        }

        return $pageIds;
    }

    /**
     * Returns if the value is going down or up in a period
     * 1: better
     * 0: no change
     * -1: degraded
     *
     * @return int
     */
    public static function getTrendOfPage(int $pageId, $strategy, $field) : int {

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_pagespeedinsights_results');

        $conditions = [];

        if (!empty($pageId)) {
            $conditions[] = $queryBuilder->expr()->eq('page_id', $pageId);
        }
        if (!empty($strategy)) {
            $conditions[] = $queryBuilder->expr()->eq('strategy', $queryBuilder->createNamedParameter($strategy));
        }

        // get the best value

        $data = $queryBuilder
            ->addSelectLiteral(
                $queryBuilder->expr()->max($field, 'max')
            )
            ->from('tx_pagespeedinsights_results')
            ->where(...$conditions)
            ->executeQuery()
            ->fetchNumeric();
        $maxValue = (int)$data['max'];


        // get the last value

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tx_pagespeedinsights_results');

        $constraints = [
            $queryBuilder->expr()->eq('t3ver_id', 0)
        ];
        if ($pageId > 0) {
            $constraints[] = $queryBuilder->expr()->eq('page_id', $pageId);
        }
        if (!empty($strategy)) {
            $constraints[] = $queryBuilder->expr()->eq('strategy', $queryBuilder->createNamedParameter($strategy));
        }
        $data = $queryBuilder
            ->select($field.' as value')
            ->from('tx_pagespeedinsights_results')
            ->where(...$constraints)
            ->orderBy('tstamp', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchNumeric();

        $currentValue = (int)$data['value'];

        if ($maxValue === $currentValue) {
            return 0;
        }
        return ($maxValue > $currentValue) ? -1 : 1;
    }


}
