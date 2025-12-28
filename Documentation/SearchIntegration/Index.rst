.. include:: /Includes.rst.txt

.. _search-integration:

==================
Search Integration
==================

Spark DMS documents can be indexed and made searchable using TYPO3's 
built-in indexed_search extension.

Overview
========

There are two approaches to indexing DMS documents:

1. **Page-based indexing**: The crawler visits document detail pages and 
   indexes the rendered HTML
2. **Database Records indexing**: Configure indexed_search to directly 
   index the document table

This guide covers the **Database Records** approach, which is recommended 
for DMS documents.

Requirements
============

- TYPO3 extension `indexed_search` (part of TYPO3 Core)
- TYPO3 extension `crawler` (tomasnorre/crawler)
- A cron job to process the crawler queue

Installation
============

1. Install the crawler extension:

   .. code-block:: bash

      composer require tomasnorre/crawler

2. Set up a cron job for the crawler (refer to crawler documentation)

Creating the Indexing Configuration
===================================

1. Go to **Web** > **List**
2. Navigate to the page that contains your **Pi2 (Detail View)** plugin
3. Create a new record of type **Indexing Configuration**
4. Configure as follows:

Basic Settings
--------------

.. confval:: Type

   :Value: Database Records

   Select "Database Records" to index table records.

.. confval:: Title

   :Value: DMS Documents

   A descriptive title for this configuration.

Database Record Settings
------------------------

.. confval:: Table to index

   :Value: Document (tx_sparkdms_domain_model_document)

   Select the Document table.

.. confval:: Alternative Source Page

   :Value: (your DMS storage folder)

   The page/folder containing the Document records.

.. confval:: Fields

   :Value: title,registry_number

   Comma-separated list of fields to index.

   .. tip::
      When you add a description field later, update this to:
      `title,registry_number,description`

.. confval:: GET parameter string

   :Value: &tx_sparkdms_pi2[action]=show&tx_sparkdms_pi2[document]=###UID###

   The URL parameters used to display a single document. The `###UID###` 
   placeholder is replaced with each document's UID.

Save the configuration.

Running the Indexer
===================

After creating the configuration:

1. The crawler will automatically pick up the configuration
2. It will create queue entries for each document
3. The cron job processes these entries
4. Each document's detail page is visited and indexed

You can manually trigger indexing:

1. Go to **Admin Tools** > **Crawler**
2. Find your indexing configuration
3. Click to add entries to the queue

Verifying the Index
===================

To verify documents are indexed:

1. Go to **Admin Tools** > **Indexed Search**
2. Check the index statistics
3. Search for a known document title

Search Results
==============

When users search, DMS documents will appear in results with:

- **Title**: The document title
- **Link**: Points to the document detail page

Customizing Indexed Content
===========================

The indexed content is taken from what's rendered on the detail page. 
To include more information in the search index:

1. Edit `Resources/Private/Templates/Document/Show.html` (or your override)
2. Ensure all searchable fields are rendered in the HTML
3. Re-run the indexer

Example Show template with comprehensive content:

.. code-block:: html

   <article class="dms-document">
       <h1>{document.title}</h1>
       <p class="dms-registry">{document.registryNumber}</p>
       <p class="dms-date">
           <f:format.date format="d.m.Y">{document.documentDate}</f:format.date>
       </p>
       <f:if condition="{document.type}">
           <p class="dms-type">{document.type.title}</p>
       </f:if>
       <f:for each="{document.category}" as="cat">
           <span class="dms-category">{cat.title}</span>
       </f:for>
       <!-- Future: description field -->
       <!-- <div class="dms-description">{document.description}</div> -->
   </article>

Troubleshooting
===============

Documents not appearing in search
---------------------------------

1. Verify the Indexing Configuration is correct
2. Check that the crawler cron job is running
3. Ensure documents have a valid detail page with Pi2 plugin
4. Check the crawler log for errors

Search returning old data
-------------------------

Re-run the indexer to update the index after document changes.

Protected documents
-------------------

Protected documents (login required) may not be indexed by the crawler 
unless you configure crawler authentication. Consider whether you want 
protected documents in search results.
