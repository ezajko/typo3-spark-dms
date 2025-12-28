.. include:: /Includes.rst.txt

.. _editor:

============
Editor Guide
============

This section provides guidance for editors on how to use Spark DMS in the 
TYPO3 backend.

Creating Document Types
=======================

Document Types categorize your documents (e.g., Report, Regulation, Form).

1. Go to **Web** > **List**
2. Navigate to your DMS storage folder
3. Click the **+** button to create a new record
4. Select **Document Type**
5. Fill in the **Title** field
6. Save the record

Creating Document Categories
============================

Categories provide hierarchical organization for your documents.

1. Go to **Web** > **List**
2. Navigate to your DMS storage folder
3. Click the **+** button to create a new record
4. Select **Document Category**
5. Fill in:

   - **Title**: The category name
   - **Parent**: (optional) Select a parent category for nesting

6. Save the record

Creating Documents
==================

To create a new document:

Using the Backend Module
------------------------

1. Go to **Spark DMS** > **Document Manager** in the left menu
2. Click the **+** button in the header
3. Fill in the document details (see below)
4. Save the record

Using the List Module
---------------------

1. Go to **Web** > **List**
2. Navigate to your DMS storage folder
3. Click the **+** button
4. Select **Document**
5. Fill in the document details
6. Save the record

Document Fields
---------------

.. confval:: Title

   :Required: Yes

   The document title displayed in lists and detail views.

.. confval:: Registry Number

   :Required: No

   An official registry or reference number for the document.

.. confval:: Document Date

   :Required: No

   The official date of the document (not the upload date).

.. confval:: Type

   :Required: No

   Select a document type from the available options.

.. confval:: Categories

   :Required: No

   Select one or more categories for the document.

.. confval:: Is Protected

   :Required: No
   :Default: No

   When enabled, only logged-in users can access/download the document.

.. confval:: Related Documents

   :Required: No

   Link to other related documents for cross-referencing.

Adding Document Versions
========================

Documents support versioning. Each version can have its own file attachment.

1. Edit an existing document
2. In the **Versions** section, click **Create new**
3. Attach a file to the version
4. Optionally add a version note/description
5. Save the document

The most recent version's file is automatically used as the document's 
primary download.

Using the Backend Module
========================

The Spark DMS Backend Module provides an overview of all documents with 
filtering and sorting capabilities.

Accessing the Module
--------------------

Go to **Spark DMS** > **Document Manager** in the left menu.

Filtering Documents
-------------------

Use the filter form at the top of the module:

- **Search**: Filter by title or registry number
- **Type**: Filter by document type
- **Category**: Filter by category
- **Date From/To**: Filter by document date range

Click **Filter** to apply, or **Reset Filters** to clear.

Sorting Documents
-----------------

Click on any column header to sort:

- **Title**: Alphabetical order
- **Registry #**: By registry number
- **Doc Date**: By document date
- **Modified**: By last modification time (default)

Click again to toggle ascending/descending order.

Adding Plugins to Pages
=======================

List Plugin (Pi1)
-----------------

1. Go to the page where you want to display the document list
2. Create a new content element
3. Select **Plugins** > **Spark DMS: List / Download**
4. Configure the FlexForm settings (see :ref:`configuration`)
5. Save and publish

Detail Plugin (Pi2)
-------------------

1. Create a separate page for document details
2. Add a new content element
3. Select **Plugins** > **Spark DMS: Detail View**
4. Save and publish
5. In your List plugin settings, set the **Detail Page** to this page

Frontend Filtering
==================

When **Show Frontend Filter** is enabled in the List plugin settings, 
users see a filter form with:

- **Search**: Text search in document titles
- **Category**: Dropdown of available categories
- **Type**: Dropdown of available types
- **Year**: Filter by document year

The filter options are automatically limited based on the FlexForm constraints 
you've configured.
