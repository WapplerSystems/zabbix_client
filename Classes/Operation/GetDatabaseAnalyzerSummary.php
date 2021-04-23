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
use TYPO3\CMS\Core\Database\Schema\SqlReader;
use WapplerSystems\ZabbixClient\OperationResult;
use TYPO3\CMS\Core\Database\Schema\SchemaMigrator;
use TYPO3\CMS\Core\Database\Schema\Exception\StatementException;

/**
 * Information about database schema updates.
 */
class GetDatabaseAnalyzerSummary implements IOperation, SingletonInterface
{
    /**
     * @param array $parameter None
     * @return OperationResult the current application context
     */
    public function execute($parameter = [])
    {
        try {
            $values = [];
            $sqlReader = GeneralUtility::makeInstance(SqlReader::class);
            $sqlStatements = $sqlReader->getCreateTableStatementArray($sqlReader->getTablesDefinitionString());
            $schemaMigrationService = GeneralUtility::makeInstance(SchemaMigrator::class);
            $addCreateChange = $schemaMigrationService->getUpdateSuggestions($sqlStatements);
            $addCreateChange = array_merge_recursive(...array_values($addCreateChange));
            if (!empty($addCreateChange['add'])) {
                $values[] = 'NewField';
            }
            if (!empty($addCreateChange['create_table'])) {
                $values[] = 'NewTable';
            }
            if (!empty($addCreateChange['change'])) {
                $values[] = 'ChangedField';
            }
            if (!empty($addCreateChange['change_currentValue'])) {
                $values[] = 'ChangedTable';
            }

            // Difference from current to expected
            $dropRename = $schemaMigrationService->getUpdateSuggestions($sqlStatements, true);
            $dropRename = array_merge_recursive(...array_values($dropRename));
            if (!empty($dropRename['change'])) {
                $values[] = 'UnusedField';
            }
            if (!empty($dropRename['change_table'])) {
                $values[] = 'UnusedTable';
            }
            if (!empty($dropRename['drop'])) {
                $values[] = 'DropTable';
            }
            if (!empty($dropRename['drop_table'])) {
                $values[] = 'DropField';
            }

            return new OperationResult(true, implode(',', $values));

        } catch (StatementException $e) {
            // Ignore
        }

        return new OperationResult(false, '');
    }
}
