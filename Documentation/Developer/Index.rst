.. include:: /Includes.rst.txt

.. _developer:

=========
Developer
=========

This section provides technical information for developers who want to 
extend or customize Spark DMS.

Architecture Overview
=====================

Spark DMS follows the standard TYPO3 Extbase/Fluid architecture:

- **Models**: Domain objects representing documents, versions, types, categories
- **Repositories**: Data access layer for querying models
- **Controllers**: Handle frontend and backend requests
- **Templates**: Fluid templates for rendering views

Domain Models
=============

Document
--------

:Path: `RootBa\SparkDms\Domain\Model\Document`
:Table: `tx_sparkdms_domain_model_document`

The main document entity.

Properties:

- `title` (string): Document title
- `registryNumber` (string): Official registry/reference number
- `uuid` (string): Unique identifier (auto-generated)
- `documentDate` (DateTime): Official document date
- `isProtected` (bool): Whether login is required
- `type` (DocumentType): Document type relation
- `category` (ObjectStorage<DocumentCategory>): Categories (MM relation)
- `versions` (ObjectStorage<DocumentVersion>): Version records
- `relatedDocuments` (ObjectStorage<Document>): Related documents

Helper Methods:

.. code-block:: php

   // Get the latest version
   $latestVersion = $document->getLatestVersion();

   // Get sorted versions (newest first)
   $versions = $document->getSortedVersions();

   // Get the file from latest version
   $file = $document->getFile();

DocumentVersion
---------------

:Path: `RootBa\SparkDms\Domain\Model\DocumentVersion`
:Table: `tx_sparkdms_domain_model_document_version`

Represents a version of a document.

Properties:

- `versionNumber` (string): Version identifier
- `file` (FileReference): Attached file
- `createdAt` (DateTime): Version creation timestamp
- `document` (Document): Parent document

DocumentType
------------

:Path: `RootBa\SparkDms\Domain\Model\DocumentType`
:Table: `tx_sparkdms_domain_model_document_type`

Properties:

- `title` (string): Type name

DocumentCategory
----------------

:Path: `RootBa\SparkDms\Domain\Model\DocumentCategory`
:Table: `tx_sparkdms_domain_model_document_category`

Properties:

- `title` (string): Category name
- `parent` (DocumentCategory): Parent category for hierarchy

Repositories
============

DocumentRepository
------------------

:Path: `RootBa\SparkDms\Domain\Repository\DocumentRepository`

Key Methods:

.. code-block:: php

   // Find documents by demand object
   public function findByDemand(DocumentDemand $demand): QueryResultInterface

   // Find all document years (for filter dropdown)
   public function findAllYears(): array

DocumentDemand
--------------

:Path: `RootBa\SparkDms\Domain\Model\Dto\DocumentDemand`

A Data Transfer Object for filtering documents.

.. code-block:: php

   $demand = new DocumentDemand();
   $demand->setSearch('annual report');
   $demand->setType($documentType);
   $demand->setCategory($category);
   $demand->setAllowedTypes([1, 2, 3]);
   $demand->setAllowedCategories([5, 6]);
   $demand->setDateFrom(new \DateTime('2024-01-01'));
   $demand->setDateTo(new \DateTime('2024-12-31'));
   $demand->setLimit(10);

   $documents = $this->documentRepository->findByDemand($demand);

Controllers
===========

Frontend Controller
-------------------

:Path: `RootBa\SparkDms\Controller\DocumentController`

Actions:

- `listAction(int $currentPage, ?array $filter)`: Display document list
- `showAction(?Document $document)`: Display document detail
- `downloadAction(Document $document, ?DocumentVersion $version)`: Download file

Backend Controller
------------------

:Path: `RootBa\SparkDms\Controller\Backend\DocumentController`

Actions:

- `listAction()`: Backend document list with filtering/sorting
- `indexAction()`: Alias for list

Uses Doctrine DBAL QueryBuilder for native TYPO3 integration.

Customizing Templates
=====================

Override templates in your site package:

.. code-block:: typoscript

   plugin.tx_sparkdms_pi1 {
       view {
           templateRootPaths.10 = EXT:my_sitepackage/Resources/Private/Extensions/SparkDms/Templates/
           partialRootPaths.10 = EXT:my_sitepackage/Resources/Private/Extensions/SparkDms/Partials/
       }
   }

Available View Partials
-----------------------

List views (in `Partials/Document/List/`):

- `Default.html`: Table layout
- `Simple.html`: Minimal list
- `Card.html`: Card grid layout
- `Folder.html`: Folder navigation
- `List.html`: Fallback table (with pagination)
- `Filter.html`: Frontend filter form
- `Debug.html`: Debug output

Creating Custom Views
---------------------

1. Create a new partial at `Partials/Document/List/MyView.html`
2. Register it in PageTS:

   .. code-block:: typoscript

      TCEFORM.tt_content.pi_flexform.sparkdms_pi1.sDEF.settings\.view\.viewType {
          addItems {
              MyView = My Custom View
          }
      }

3. The dispatcher will automatically load your partial

Hooks and Events
================

DataHandler Hook
----------------

:Path: `RootBa\SparkDms\Hook\DataHandlerHook`

Handles file organization when documents are saved.

Extending the Extension
=======================

Adding Fields to Document
-------------------------

1. Create `Configuration/TCA/Overrides/tx_sparkdms_domain_model_document.php`:

   .. code-block:: php

      <?php
      $GLOBALS['TCA']['tx_sparkdms_domain_model_document']['columns']['my_field'] = [
          'label' => 'My Field',
          'config' => [
              'type' => 'input',
          ],
      ];

      \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
          'tx_sparkdms_domain_model_document',
          'my_field',
          '',
          'after:title'
      );

2. Add the property to your extended model
3. Run database schema update

API Reference
=============

For detailed API documentation, refer to the source code in the `Classes/` 
directory. All public methods are documented with PHPDoc comments.

CLI Commands
============

Spark DMS provides command-line tools for document management.

dms:import
----------

Import a new document or add a version to an existing document.

**Create new document:**

.. code-block:: bash

   ddev typo3 dms:import \
     --file=/path/to/document.pdf \
     --title="Document Title" \
     --type=1 \
     --category=5 --category=8 \
     --registry-number="DOC-001" \
     --date=2025-01-15 \
     --pid=184

**Add new version to existing document:**

.. code-block:: bash

   ddev typo3 dms:import \
     --file=/path/to/updated.pdf \
     --document=42 \
     --version-comment="Updated content"

Options:

- ``--file, -f``: Path to the file (required)
- ``--title, -t``: Document title (required for new documents)
- ``--type``: Document type UID
- ``--category, -c``: Category UID (repeatable for multiple)
- ``--registry-number, -r``: Registry number
- ``--date, -d``: Document date (Y-m-d format)
- ``--description``: Document description
- ``--pid, -p``: Storage page ID
- ``--document``: Existing document UID (for adding version)
- ``--version-comment``: Comment for version

dms:list
--------

List documents with optional filtering.

.. code-block:: bash

   ddev typo3 dms:list --type=1 --limit=20 --format=table

Options:

- ``--type, -t``: Filter by document type UID
- ``--category, -c``: Filter by category UID
- ``--search, -s``: Search in document title
- ``--limit, -l``: Maximum number of results (default: 50)
- ``--format, -f``: Output format: table, json, csv (default: table)

dms:export
----------

Export documents to CSV or JSON file.

.. code-block:: bash

   ddev typo3 dms:export --format=csv --output=/tmp/documents.csv

Options:

- ``--type, -t``: Filter by document type UID
- ``--category, -c``: Filter by category UID
- ``--output, -o``: Output file path (default: stdout)
- ``--format, -f``: Export format: csv, json (default: csv)

File Organization Service
=========================

:Path: `RootBa\\SparkDms\\Service\\FileOrganizationService`

Automatically organizes uploaded files from ``/_inbox/`` to structured folders:

.. code-block:: text

   /{type-slug}/{year}/{month}/{title-slug}-{uuid}/filename.pdf

Example:

.. code-block:: text

   /odluka/2025/12/annual-report-ab12cd34/report.pdf

The service is triggered:

- Automatically via DataHandler hook when saving documents in backend
- Directly by CLI import command after creating documents/versions

To manually organize files for a document:

.. code-block:: php

   $fileOrganizationService = GeneralUtility::makeInstance(FileOrganizationService::class);
   $fileOrganizationService->organizeDocumentFiles($documentUid);

