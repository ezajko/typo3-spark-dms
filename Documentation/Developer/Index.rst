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

:Path: `EtfUnsa\SparkDms\Domain\Model\Document`
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

:Path: `EtfUnsa\SparkDms\Domain\Model\DocumentVersion`
:Table: `tx_sparkdms_domain_model_document_version`

Represents a version of a document.

Properties:

- `versionNumber` (string): Version identifier
- `file` (FileReference): Attached file
- `createdAt` (DateTime): Version creation timestamp
- `document` (Document): Parent document

DocumentType
------------

:Path: `EtfUnsa\SparkDms\Domain\Model\DocumentType`
:Table: `tx_sparkdms_domain_model_document_type`

Properties:

- `title` (string): Type name

DocumentCategory
----------------

:Path: `EtfUnsa\SparkDms\Domain\Model\DocumentCategory`
:Table: `tx_sparkdms_domain_model_document_category`

Properties:

- `title` (string): Category name
- `parent` (DocumentCategory): Parent category for hierarchy

Repositories
============

DocumentRepository
------------------

:Path: `EtfUnsa\SparkDms\Domain\Repository\DocumentRepository`

Key Methods:

.. code-block:: php

   // Find documents by demand object
   public function findByDemand(DocumentDemand $demand): QueryResultInterface

   // Find all document years (for filter dropdown)
   public function findAllYears(): array

DocumentDemand
--------------

:Path: `EtfUnsa\SparkDms\Domain\Model\Dto\DocumentDemand`

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

:Path: `EtfUnsa\SparkDms\Controller\DocumentController`

Actions:

- `listAction(int $currentPage, ?array $filter)`: Display document list
- `showAction(?Document $document)`: Display document detail
- `downloadAction(Document $document, ?DocumentVersion $version)`: Download file

Backend Controller
------------------

:Path: `EtfUnsa\SparkDms\Controller\Backend\DocumentController`

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

:Path: `EtfUnsa\SparkDms\Hook\DataHandlerHook`

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
