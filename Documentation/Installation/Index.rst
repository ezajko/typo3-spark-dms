.. include:: /Includes.rst.txt

.. _installation:

============
Installation
============

Requirements
============

- TYPO3 CMS 13.4 or higher
- PHP 8.2 or higher
- Composer-based TYPO3 installation

Installation via Composer
=========================

The recommended way to install Spark DMS is via Composer:

.. code-block:: bash

   composer require rootba/typo3-spark-dms

Alternatively, if installing from a local package:

.. code-block:: bash

   composer require rootba/typo3-spark-dms:@dev

Activate the Extension
======================

After installation, activate the extension in the TYPO3 backend:

1. Go to **Admin Tools** > **Extensions**
2. Find "Spark DMS" in the list
3. Click the activation icon

Or via CLI:

.. code-block:: bash

   vendor/bin/typo3 extension:activate spark_dms

Site Configuration
==================

Spark DMS uses TYPO3 Site Settings for global configuration. Add the following 
to your site's `config/sites/<site-identifier>/settings.yaml`:

.. code-block:: yaml

   sparkdms:
     recordStoragePid: 123

Replace `123` with the UID of the folder/page where your DMS records will be stored.

Include TypoScript
==================

Include the extension's TypoScript in your site:

1. Go to **Web** > **Template**
2. Select your root page
3. Edit the template record
4. In the "Includes" tab, add "Spark DMS" to the selected items

Database Tables
===============

The extension creates the following database tables:

- `tx_sparkdms_domain_model_document` - Document records
- `tx_sparkdms_domain_model_documentversion` - Document versions
- `tx_sparkdms_domain_model_documenttype` - Document types
- `tx_sparkdms_domain_model_documentcategory` - Document categories

All tables are created automatically during extension activation.

File Storage
============

Document files are stored in the TYPO3 fileadmin. By default, uploaded files 
are organized into folders based on the document date structure (YYYY/MM/).

.. tip::
   Configure a dedicated file storage for DMS files for better organization.

Next Steps
==========

After installation:

1. Create Document Types (see :ref:`editor`)
2. Create Document Categories (see :ref:`editor`)
3. Add the DMS plugins to your pages (see :ref:`configuration`)
