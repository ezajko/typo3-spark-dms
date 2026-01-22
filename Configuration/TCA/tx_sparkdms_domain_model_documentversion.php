<?php

return [
    'ctrl' => [
        'title' => 'Document Version',
        'label' => 'version_label',
        'label_alt' => 'file',
        'label_alt_force' => true,
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
        'hideTable' => false,// Inline only
        'searchFields' => 'version_label',
        'iconfile' => 'EXT:spark_dms/Resources/Public/Icons/DocumentVersion.svg',
        'security' => [
            'ignorePageTypeRestriction' => true,
        ],
    ],
    'types' => [
        '1' => ['showitem' => '
            --div--;General, version_label, file, uuid,
            --div--;Language, sys_language_uid, l10n_parent, l10n_diffsource,
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
                'foreign_table' => 'tx_sparkdms_domain_model_documentversion',
                'foreign_table_where' => 'AND {#tx_sparkdms_domain_model_documentversion}.{#sys_language_uid} = 0',
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
        'version_label' => [
            'exclude' => true,
            'label' => 'Version Label (e.g. v1.0, Draft)',
            'config' => ['type' => 'input', 'eval' => 'trim', 'placeholder' => 'v1.0'],
        ],
        'file' => [
            'exclude' => true,
            'label' => 'File',
            'config' => [
                'type' => 'file',
                'maxitems' => 1,
                'allowed' => 'pdf,doc,docx,xls,xlsx,ppt,pptx,odt,ods,odp',
            ],
        ],
        'document' => [
            'config' => ['type' => 'passthrough'],
        ],
        'uuid' => [
            'exclude' => true,
            'label' => 'UUID',
            'config' => ['type' => 'uuid'],
        ],
        'created_at' => [
            'exclude' => true,
            'label' => 'Created At',
            'config' => [
                'type' => 'datetime',
                'format' => 'datetime',
                'default' => 'now',
                'readOnly' => true,
            ],
        ],
    ],
];
