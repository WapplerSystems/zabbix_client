.. include:: ../Includes.txt


.. _howtos:

=======
How Tos
=======

.. _extensionupdate:

Monitoring an extension update
==============================

TODO


.. _pagespeed:

Page Speed
==========

TODO


.. _sslcheck:

SSL validation and expiration check
===================================

TODO


.. _cvemonitoring:

Monitoring known vulnerabilities (CVE)
======================================

The extension reports *which packages are installed*; the matching against the CVE data
happens in Zabbix. That split keeps the monitored site free of any outgoing connection,
of the ``composer`` binary and of ``exec()``, which are typically unavailable on managed
hosting.

Import the template ``Resources/Private/ZabbixTemplate/zabbix-7/composer-security.yaml``
(the ``.json`` next to it has identical content) and assign
:guilabel:`TYPO3 Composer Security` to the host in addition to the general TYPO3 template.
Only ``{$TYPO3_CLIENT_KEY}`` has to be set, the remaining macros have working defaults.

How it works
------------

#. The item ``typo3.composer.packages`` fetches the inventory from
   :ref:`GetComposerPackages <items_getcomposerpackages>` once per hour.
#. The dependent item ``typo3.composer.audit`` runs a JavaScript step that sends the
   inventory to the OSV API. OSV performs the version matching and answers with the
   GitHub advisory ids that apply to exactly these versions.
#. The same step asks the Packagist advisory API for the affected packages and enriches
   every hit with CVE id, severity, title and link. Both sides are joined on the GitHub
   advisory id.
#. Dependent items split the result into counters per severity plus a readable list in
   ``typo3.composer.audit.list``. The triggers work on the counters.

The check needs two HTTP requests per run and takes well under a second for a typical
installation. That is not an accident: JavaScript preprocessing in Zabbix is limited to
ten seconds, which rules out asking the vulnerability database for each finding
separately.

Requirements
------------

The Zabbix server or proxy needs outgoing HTTPS to ``api.osv.dev`` and
``packagist.org``. Package names and versions of the monitored sites are transmitted in
the process. Where that is not acceptable, OSV also publishes its Packagist data as a
downloadable archive, which allows a local mirror and a matching script on the Zabbix
server instead — at the price of implementing the version comparison yourself.

Packages from private repositories are unknown to both services. They produce no hit,
but they are not covered either.

Accepting a finding
-------------------

``{$TYPO3_AUDIT_IGNORE}`` takes a comma separated list of CVE or GHSA ids that should be
ignored, for instance for a vulnerability in a transitive dependency that is not
reachable in the installation.

When the check itself fails
---------------------------

A failing check must not look like a clean result. If OSV or Packagist cannot be
reached, the audit item reports only an error and the severity counters keep their last
value, which the trigger :guilabel:`composer security audit failed` reports separately.
So a broken check is visible as a broken check, never as an all-clear.


.. _combining:

Combining with other systems
============================




