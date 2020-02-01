.. include:: ../Includes.txt


.. _troubleshooting:

===============
Troubleshooting
===============

.. _nodata:

I do not receive any data from my TYPO3 website
===============================================

Try to retrieve the JSON output via browser.

For TYPO3 systems >= 9:

``https://<domain>/zabbixclient/?key=<your-key>&operation=GetTYPO3Version``

For TYPO3 systems < 9:

``https://<domain>/index.php?eID=zabbixclient&key=<your-key>&operation=GetTYPO3Version``



