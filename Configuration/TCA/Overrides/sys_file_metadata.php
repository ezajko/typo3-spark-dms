<?php

declare(strict_types=1);

defined('TYPO3') or die();

/**
 * Extend sys_file_metadata with DMS-specific fields
 * 
 * @author Ernedin Zajko <ezajko@root.ba>
 */

// Add DMS fields to sys_file_metadata
$tempColumns = [
    'tx_sparkdms_document' => [
        'exclude' => true,
        'label' => 'DMS Document',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectSingle',
            'foreign_table' => 'tx_sparkdms_domain_model_document',
            'items' => [
                ['label' => '', 'value' => 0],
            ],
            'default' => 0,
            'readOnly' => true,
        ],
    ],
    'tx_sparkdms_is_protected' => [
        'exclude' => true,
        'label' => 'DMS Protected File',
        'config' => [
            'type' => 'check',
            'renderType' => 'checkboxToggle',
            'default' => 0,
            'readOnly' => true,
        ],
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', $tempColumns);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
    'sys_file_metadata',
    'tx_sparkdms_document, tx_sparkdms_is_protected',
    '',
    'after:description'
);
