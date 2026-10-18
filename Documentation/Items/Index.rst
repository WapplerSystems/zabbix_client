.. include:: ../Includes.txt


.. _items:

=====
Items
=====

This extension supports ...


.. _items_getcomposerpackages:

GetComposerPackages
===================

Returns the installed composer packages with their versions, so that an external system
can match them against a vulnerability database. The data comes from the composer runtime
API, so neither the ``composer`` binary nor ``exec()`` is required.

.. code-block:: none

   https://example.org/zabbixclient/?operation=GetComposerPackages

Parameters
----------

.. t3-field-list-table::
 :header-rows: 1

 - :Parameter:
     Parameter
   :Default:
     Default
   :Description:
     Description

 - :Parameter:
     includeDev
   :Default:
     0
   :Description:
     Also report packages that are only required for development.

 - :Parameter:
     types
   :Default:
     *(empty)*
   :Description:
     Comma separated list of composer package types to restrict the result to,
     for example ``typo3-cms-extension,typo3-cms-framework``.

Result
------

.. code-block:: json

   {
     "status": true,
     "value": {
       "count": 99,
       "packages": {"typo3/cms-core": "13.4.16", "guzzlehttp/guzzle": "7.11.0"},
       "unresolved": {"apache-solr-for-typo3/solr": "dev-main"},
       "root": "acme/website",
       "generated": 1758450000
     }
   }

``packages`` contains every package with a comparable release version, ``count`` is its
size. Four groups are deliberately left out of it:

*  The root package, which is the project itself and not a published package.
*  Replaced and provided virtual packages, which have no code and no version.
*  Packages required only for development, unless ``includeDev`` is set.
*  Packages installed from a branch, for example ``dev-main`` or ``13.4.x-dev``. These
   are reported in ``unresolved`` instead.

The last group matters for the vulnerability match. A branch name is not a comparable
version, and a vulnerability database that cannot parse it reports *every* advisory of
that package as a hit. Reporting such packages separately keeps them visible without
turning them into permanent false alarms.

A leading ``v`` is stripped from versions, so ``v13.4.16`` and ``13.4.16`` are reported
the same way.

If the installation does not provide the composer runtime API, the operation returns
``{"status": false, "value": "Composer runtime API not available"}``.


.. _items_composer_commands:

GetComposerAudit, GetComposerOutdated, GetComposerVersion
=========================================================

These operations run the ``composer`` binary on every call, so the result is current at
the moment Zabbix asks. They need ``proc_open()`` and a composer executable on the server.

.. code-block:: none

   https://example.org/zabbixclient/?operation=GetComposerAudit
   https://example.org/zabbixclient/?operation=GetComposerOutdated
   https://example.org/zabbixclient/?operation=GetComposerVersion

The web server environment differs from a shell, and the operations compensate for it:

*  ``HOME`` is often not set, and composer refuses to start without ``HOME`` or
   ``COMPOSER_HOME``. The home directory of the system user is set instead, so the global
   composer cache and ``auth.json`` are shared with the shell; if it is not writable,
   ``var/composer`` is used as ``COMPOSER_HOME``. If ``~/.config/composer`` exists, it is
   set as ``COMPOSER_HOME`` explicitly: composer only picks that directory when ``XDG_*``
   variables are set, as in an SSH session, and would otherwise miss its ``auth.json``.
*  ``PHP_BINARY`` points to php-fpm or php-cgi. Composer is started with the PHP CLI of
   the same version (``php85``, ``php8.5``, ``php85.bin.cli`` or ``php``), or with the
   path set as ``program.php`` in the extension configuration. ``/usr/local/bin`` and
   ``/usr/bin`` are searched before ``PHP_BINDIR``, and every candidate must report the
   ``cli`` SAPI: on Hetzner managed hosting ``PHP_BINDIR`` is ``/home/httpd/cgi-bin``,
   whose ``php85`` is a CGI wrapper that answers with a security alert.
*  The composer binary is looked up in ``vendor/composer/composer``, ``composer.phar`` in
   the project root, ``~/bin``,
   ``/usr/local/bin`` and ``/usr/bin``, or taken from ``program.composer``.
*  PHP notices go to stderr, so deprecation warnings of an older composer on a newer PHP
   do not corrupt the JSON output.

``GetComposerAudit`` and ``GetComposerOutdated`` accept a ``timeout`` parameter in
seconds (default 50). ``composer outdated`` queries every configured repository and
typically takes 10 to 30 seconds — set the timeout of the Zabbix item accordingly and
query it no more than once a day. ``composer audit`` takes well under a second.

``GetComposerOutdated`` accepts ``direct`` (default ``1``): only packages required in
``composer.json`` are reported.

Results
-------

.. code-block:: json

   {"status": true, "value": {
     "vulnerabilities": 1,
     "advisories": [{"package": "enshrined/svg-sanitize", "cve": "CVE-2026-107380",
       "title": "…", "affectedVersions": "<=0.22.0", "link": "https://github.com/advisories/…"}],
     "abandonedCount": 0, "abandoned": []
   }}

.. code-block:: json

   {"status": true, "value": {
     "outdatedCount": 3, "majorCount": 1, "minorCount": 1, "patchCount": 1, "direct": true,
     "packages": [{"package": "georgringer/news", "version": "14.0.3", "latest": "14.1.2",
       "status": "semver-safe-update", "level": "minor", "abandoned": false}]
   }}

.. code-block:: json

   {"status": true, "value": {
     "version": "2.5.5", "latest": "2.10.3", "updateAvailable": true,
     "binary": "/usr/bin/composer", "php": "/usr/bin/php85"
   }}

``updateAvailable`` is ``null`` when getcomposer.org could not be reached. Packages
installed from a branch get an empty ``level``.

When composer cannot run, the operations return ``status: false`` with the reason, for
example ``Cannot run composer audit: composer audit returned no JSON (exit code 1): The
HOME or COMPOSER_HOME environment variable must be set …``. A failed check must never be
read as a clean result.
