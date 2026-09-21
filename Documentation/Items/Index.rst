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
