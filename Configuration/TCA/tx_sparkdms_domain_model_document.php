<?php

return [
    'ctrl' => [
        'title' => 'Document',
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
        'searchFields' => 'title',
        'iconfile' => 'EXT:spark_dms/Resources/Public/Icons/Document.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;General, title, registry_number, type, category, document_date, is_protected, uuid,
            --div--;Versions, versions,
            --div--;Related, related_documents,
            --div--;Language, sys_language_uid, l10n_parent, l10n_diffsource,
            --div--;Access, hidden, starttime, endtime,
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
            'label' => 'Title',
            'config' => ['type' => 'input', 'eval' => 'trim', 'required' => true],
        ],
        'registry_number' => [
            'exclude' => true,
            'label' => 'Registry Number (Djelovodni broj)',
            'config' => ['type' => 'input', 'eval' => 'trim', 'size' => 30],
        ],
        'type' => [
            'exclude' => true,
            'label' => 'Document Type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_sparkdms_domain_model_document_type',
                'items' => [
                    ['label' => '-- Select Type --', 'value' => 0],
                ],
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'category' => [
            'exclude' => true,
            'label' => 'Context Category',
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
            'label' => 'Document Date',
            'config' => [
                'type' => 'datetime',
                'format' => 'date',
                'default' => 0,
            ],
        ],
        'is_protected' => [
            'exclude' => true,
            'label' => 'Protected (Auth Only)',
            'config' => ['type' => 'check', 'renderType' => 'checkboxToggle'],
        ],
        'versions' => [
            'exclude' => true,
            'label' => 'File Versions',
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
                    'newRecordLinkTitle' => 'Add Version',
                ],
            ],
        ],
        'uuid' => [
            'exclude' => true,
            'label' => 'UUID',
            'config' => ['type' => 'uuid'],
        ],
        'related_documents' => [
            'exclude' => true,
            'label' => 'Related Documents',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'tx_sparkdms_domain_model_document',
                'MM' => 'tx_sparkdms_document_related_mm',
                'minitems' => 0,
                'maxitems' => 99,
            ],
        ],
    ],
];
