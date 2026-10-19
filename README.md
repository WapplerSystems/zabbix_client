# TYPO3 extension zabbix_client

This is a Zabbix client extension for TYPO3.

Whether 1 or 1000 TYPO3 systems. Keep an eye on them all and offer your customers to 
keep them up to date. This allows errors and security gaps to be detected early before they become a real problem.

Feel free to set your own alarms and parameters in Zabbix. This gives you complete control over the process and 
allows you to add other systems to your monitoring.

This extension allows a deeper scan. In combination with a superficial scan, a system scan and other scans such 
as Lighthouse tests, a system can be fully observed.

Further information can be found in the documentation.

## Zabbix templates

Templates for Zabbix 7.x, exported from a production Zabbix:

* [typo3.yaml](Resources/Private/ZabbixTemplate/zabbix-7/typo3.yaml) – general TYPO3 template
  ([download](https://raw.githubusercontent.com/WapplerSystems/zabbix_client/release/v14/Resources/Private/ZabbixTemplate/zabbix-7/typo3.yaml))
* [composer-security.yaml](Resources/Private/ZabbixTemplate/zabbix-7/composer-security.yaml) – known vulnerabilities via OSV
  ([download](https://raw.githubusercontent.com/WapplerSystems/zabbix_client/release/v14/Resources/Private/ZabbixTemplate/zabbix-7/composer-security.yaml))

Set the host macros `{$TYPO3_CLIENT_KEY}` and `{$TYPO3_CLIENT_PATH}` (e.g. `https://www.example.com/zabbixclient/`).

Website: https://t3monitoring.de/

## screenshots

![Data](https://github.com/svewap/zabbix_client/blob/master/Documentation/Images/data_overview.png?raw=true)

![Errors](https://github.com/svewap/zabbix_client/blob/master/Documentation/Images/graph_error.png?raw=true)

![Problems](https://github.com/svewap/zabbix_client/blob/master/Documentation/Images/data_overview_problems.png?raw=true)