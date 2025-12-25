<?php

return [
    'ctrl' => [
        'title' => 'Document Category',
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
        ],
        'searchFields' => 'title',
        'iconfile' => 'EXT:spark_dms/Resources/Public/Icons/DocumentCategory.svg'
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;General, title, parent, slug, uuid,
            --div--;Language, sys_language_uid, l10n_parent, l10n_diffsource,
            --div--;Access, hidden,
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
                'foreign_table' => 'tx_sparkdms_domain_model_document_category',
                'foreign_table_where' => 'AND {#tx_sparkdms_domain_model_document_category}.{#sys_language_uid} = 0',
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
        
        // Custom Fields
        'title' => [
            'exclude' => true,
            'label' => 'Title',
            'config' => ['type' => 'input', 'eval' => 'trim', 'required' => true],
        ],
        'parent' => [
            'exclude' => true,
            'label' => 'Parent Category',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectTree',
                'foreign_table' => 'tx_sparkdms_domain_model_document_category',
                'treeConfig' => [
                    'parentField' => 'parent',
                    'appearance' => ['showHeader' => true, 'expandAll' => true, 'maxLevels' => 5],
                ],
                'minitems' => 0,
                'maxitems' => 1,
            ],
        ],
        'slug' => [
            'exclude' => true,
            'label' => 'Slug',
            'config' => [
                'type' => 'slug',
                'generatorOptions' => [
                    'fields' => ['title'],
                    'fieldSeparator' => '/',
                    'replacements' => ['/' => ''],
                ],
                'fallbackCharacter' => '-',
                'eval' => 'uniqueInSite',
            ],
        ],
        'uuid' => [
            'exclude' => true,
            'label' => 'UUID',
            'config' => ['type' => 'uuid'],
        ],
    ],
];
