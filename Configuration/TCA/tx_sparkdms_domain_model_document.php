<?php

/**
 * TCA configuration for Document model
 * 
 * @author Ernedin Zajko <ezajko@root.ba>
 */

$ll = 'LLL:EXT:spark_dms/Resources/Private/Language/locallang_db.xlf:';

return [
    'ctrl' => [
        'title' => $ll . 'tx_sparkdms_domain_model_document',
        'label' => 'title',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'sortby' => 'sorting',
        'versioningWS' => true,
        'languageField' => 'sys_language_uid',
        'transOrigPointerField' => 'l10n_parent',
        'transOrigDiffSourceField' => 'l10n_diffsource',
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden',
            'starttime' => 'starttime',
            'endtime' => 'endtime',
        ],
        'searchFields' => 'title,registry_number,description',
        'iconfile' => 'EXT:spark_dms/Resources/Public/Icons/Document.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;' . $ll . 'tabs.general, title, registry_number, description, type, category, document_date, is_protected, uuid,
            --div--;' . $ll . 'tabs.versions, versions,
            --div--;' . $ll . 'tabs.related, related_documents, related_by,
            --div--;' . $ll . 'tabs.language, sys_language_uid, l10n_parent, l10n_diffsource,
            --div--;' . $ll . 'tabs.access, hidden, starttime, endtime,
        '],
    ],
    'columns' => [
        'sys_language_uid' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.language',
            'config' => ['type' => 'language'],
        ],
        'l10n_parent' => [
            'displayCond' => 'FIELD:sys_language_uid:>:0',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.l18n_parent',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['label' => '', 'value' => 0],
                ],
                'foreign_table' => 'tx_sparkdms_domain_model_document',
                'foreign_table_where' => 'AND {#tx_sparkdms_domain_model_document}.{#sys_language_uid} = 0',
            ],
        ],
        'l10n_diffsource' => [
            'config' => ['type' => 'passthrough'],
        ],
        'hidden' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.enabled',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle', 'items' => [['label' => '', 'invertStateDisplay' => true]]],
        ],
        'starttime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
            'config' => ['type' => 'datetime', 'default' => 0],
        ],
        'endtime' => [
            'exclude' => true,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
            'config' => ['type' => 'datetime', 'default' => 0, 'range' => ['upper' => 2145916800]],
        ],
        
        // Custom Fields
        'title' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.title',
            'config' => ['type' => 'input', 'eval' => 'trim', 'required' => true],
        ],
        'registry_number' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.registry_number',
            'config' => ['type' => 'input', 'eval' => 'trim', 'size' => 30],
        ],
        'description' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.description',
            'config' => [
                'type' => 'text',
                'cols' => 40,
                'rows' => 5,
                'enableRichtext' => true,
            ],
        ],
        'type' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_sparkdms_domain_model_document_type',
                'items' => [
                    ['label' => $ll . 'select.type.placeholder', 'value' => 0],
                ],
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'category' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.category',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'tx_sparkdms_domain_model_document_category',
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => ['showHeader' => true, 'expandAll' => true, 'maxLevels' => 5],
                ],
                'MM' => 'tx_sparkdms_document_category_mm',
                'minitems' => 0,
                'maxitems' => 99,
            ],
        ],
        'document_date' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.document_date',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
        ],
        'is_protected' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.is_protected',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle'],
        ],
        'versions' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.versions',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_sparkdms_domain_model_document_version',
                'foreign_field' => 'document',
                'foreign_sortby' => 'sorting',
                'maxitems' => 99,
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                    'useSortable' => true,
                    'showNewRecordLink' => true,
                    'newRecordLinkTitle' => $ll . 'button.add_version',
                ],
            ],
        ],
        'uuid' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.uuid',
            'config' => ['type' => 'uuid'],
        ],
        'related_documents' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.related_documents',
            'description' => 'Documents this document is related to',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_sparkdms_domain_model_document',
                'foreign_table_where' => 'AND {#tx_sparkdms_domain_model_document}.{#uid} != ###THIS_UID###',
                'MM' => 'tx_sparkdms_document_related_mm',
                'minitems' => 0,
                'maxitems' => 99,
            ],
        ],
        // Symmetric field: documents that have THIS document as their related
        'related_by' => [
            'exclude' => true,
            'label' => $ll . 'tx_sparkdms_domain_model_document.related_by',
            'description' => 'Documents that reference this document as related',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_sparkdms_domain_model_document',
                'foreign_table_where' => 'AND {#tx_sparkdms_domain_model_document}.{#uid} != ###THIS_UID###',
                'MM' => 'tx_sparkdms_document_related_mm',
                'MM_opposite_field' => 'related_documents',
                'minitems' => 0,
                'maxitems' => 99,
            ],
        ],
    ],
];
