<?php

namespace WapplerSystems\ZabbixClient;

/**
 * This file is part of the "zabbix_client" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */


/**
 * An Operation Result encapsulates the result of an Operation execution.
 */
class OperationResult
{
    /**
     * @var bool
     */
    protected $status;

    /**
     * @var array|string
     */
    protected $value;

    /**
     * Construct a new operation result
     *
     * @param bool $status
     * @param mixed $value
     */
    public function __construct($status, $value)
    {
        $this->status = $status;
        $this->value = $value;
    }

    /**
     * @return bool If the operation was executed successful
     */
    public function isSuccessful()
    {
        return $this->status;
    }

    /**
     * @return array|string The operation value
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array The Operation Result as an array
     */
    public function toArray()
    {
        return ['status' => $this->status, 'value' => $this->value];
    }
}
