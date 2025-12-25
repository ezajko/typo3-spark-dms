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
        'iconfile' => 'EXT:spark_dms/Resources/Public/Icons/Document.svg'
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;General, title, type, category, document_date, is_protected, uuid,
            --div--;Versions, versions,
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
        'type' => [
            'exclude' => true,
            'label' => 'Document Type',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'tx_sparkdms_domain_model_document_type',
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => ['showHeader' => true, 'expandAll' => true, 'maxLevels' => 5],
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
            'label' => 'Date',
            'config' => ['type' => 'datetime', 'dbType' => 'date', 'eval' => 'date', 'default' => 0],
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
                'foreign_sortby' => 'tstamp',
                'appearance' => [
                    'collapseAll' => true,
                    'expandSingle' => true,
                ],
            ],
        ],
        'uuid' => [
            'exclude' => true,
            'label' => 'UUID',
            'config' => ['type' => 'uuid'],
        ],
    ],
];
