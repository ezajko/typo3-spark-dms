.. include:: /Includes.rst.txt

.. _configuration:

=============
Configuration
=============

Spark DMS provides two frontend plugins that can be added to your pages as 
content elements.

Plugins
=======

Pi1: List / Download
--------------------

This plugin displays a list of documents with optional filtering and pagination.

**Available in**: New Content Element Wizard > Plugins > Spark DMS: List / Download

Pi2: Detail View
----------------

This plugin displays the detail view of a single document, including version 
history and download options.

**Available in**: New Content Element Wizard > Plugins > Spark DMS: Detail View

FlexForm Settings
=================

List Plugin (Pi1)
-----------------

View Settings
^^^^^^^^^^^^^

.. confval:: View Type

   :Type: select
   :Default: Default
   :Options: Default, Simple, Card, Folder, Debug

   Determines the visual layout of the document list.

   - **Default**: Table layout with all columns
   - **Simple**: Minimal list with just titles and dates
   - **Card**: Bootstrap card grid layout
   - **Folder**: Folder-based navigation by category
   - **Debug**: Shows all available template variables

.. confval:: Show Frontend Filter

   :Type: boolean
   :Default: 0

   When enabled, displays a filter form above the document list allowing 
   users to search and filter documents.

.. confval:: Detail Page

   :Type: page
   :Default: (none)

   The page containing the Pi2 (Detail View) plugin. Document titles will 
   link to this page.

Pagination Settings
^^^^^^^^^^^^^^^^^^^

.. confval:: Max Items (total limit)

   :Type: integer
   :Default: 0

   Maximum number of documents to display. Set to 0 for unlimited.

.. confval:: Items Per Page

   :Type: integer
   :Default: 10

   Number of documents per page when pagination is active.

Filter Constraints
^^^^^^^^^^^^^^^^^^

.. confval:: Filter by Document Types

   :Type: select (multiple)
   :Default: (all)

   Restrict the list to specific document types. Leave empty for all types.

.. confval:: Filter by Document Categories

   :Type: tree select
   :Default: (all)

   Restrict the list to specific categories. Leave empty for all categories.

.. confval:: Date From / Date To

   :Type: date
   :Default: (none)

   Restrict documents to a specific date range based on document date.

.. confval:: Selected Documents

   :Type: group
   :Default: (none)

   Manually select specific documents to display. When set, this overrides 
   all filter constraints.

Detail Plugin (Pi2)
-------------------

.. confval:: View Type

   :Type: select
   :Default: Default
   :Options: Default, Simple, Card, History, Debug

   Determines the visual layout of the document detail view.

TypoScript Configuration
========================

The extension provides TypoScript constants for customization:

.. code-block:: typoscript

   plugin.tx_sparkdms_pi1 {
       view {
           templateRootPath = EXT:my_sitepackage/Resources/Private/Extensions/SparkDms/Templates/
           partialRootPath = EXT:my_sitepackage/Resources/Private/Extensions/SparkDms/Partials/
           layoutRootPath = EXT:my_sitepackage/Resources/Private/Extensions/SparkDms/Layouts/
       }
       persistence {
           storagePid = 123
       }
   }

Default Settings
----------------

The following default settings are defined in TypoScript:

.. code-block:: typoscript

   plugin.tx_sparkdms_pi1.settings {
       view {
           viewType = Default
           frontEndFilter = 0
           detailPid =
           pagination {
               limit = 0
               itemsPerPage = 10
           }
       }
       filter {
           selectedDocuments =
           documentTypes =
           documentCategories =
           dateRange {
               dateFrom = 0
               dateTo = 0
           }
       }
   }

FlexForm values override these defaults.

PageTS Configuration
====================

Custom View Types
-----------------

You can add custom view types to the dropdown using PageTS:

.. code-block:: typoscript

   TCEFORM.tt_content.pi_flexform.sparkdms_pi1.sDEF.settings\.view\.viewType {
       addItems {
           MyCustomView = My Custom View
       }
   }

Then create a corresponding partial at:
`Resources/Private/Partials/Document/List/MyCustomView.html`

Routing Configuration
=====================

For pretty URLs, add the following to your site's `config.yaml`:

.. code-block:: yaml

   routeEnhancers:
     SparkDmsPlugin:
       type: Extbase
       extension: SparkDms
       plugin: Pi1
       routes:
         - routePath: '/document/{document_title}'
           _controller: 'Document::show'
           _arguments:
             document_title: document
       defaultController: 'Document::list'
       aspects:
         document_title:
           type: PersistedAliasMapper
           tableName: tx_sparkdms_domain_model_document
           routeFieldName: path_segment
