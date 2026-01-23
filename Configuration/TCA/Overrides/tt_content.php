<?php
/**
 * TCA Overrides for tt_content
 * Registers Spark DMS plugins and their FlexForms.
 *
 * Author: Ernedin Zajko <ezajko@root.ba>
 */

defined('TYPO3') or die();

// --- New CType Registration (Documents Group) ---

// 1. List / Download
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'Spark DMS: List / Download',
        'sparkdms_list',
        'EXT:spark_dms/Resources/Public/Icons/Extension.svg'
    ],
    'CType',
    'spark_dms'
);

// Configure ShowItem for List
$GLOBALS['TCA']['tt_content']['types']['sparkdms_list'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            --palette--;;headers,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,
            pi_flexform,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
            --palette--;;frames,
            --palette--;;appearanceLinks,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
            categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
    '
];

// Add FlexForm for List (Reuse existing)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:spark_dms/Configuration/FlexForms/List.xml',
    'sparkdms_list'
);


// 2. Detail View
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
    [
        'Spark DMS: Detail View',
        'sparkdms_detail',
        'EXT:spark_dms/Resources/Public/Icons/Extension.svg'
    ],
    'CType',
    'spark_dms'
);

// Configure ShowItem for Detail
$GLOBALS['TCA']['tt_content']['types']['sparkdms_detail'] = [
    'showitem' => '
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
            --palette--;;general,
            --palette--;;headers,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.plugin,
            pi_flexform,
        --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
            --palette--;;frames,
            --palette--;;appearanceLinks,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
            --palette--;;language,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            --palette--;;hidden,
            --palette--;;access,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
            categories,
        --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
            rowDescription,
    '
];

// Add FlexForm for Detail (Reuse existing)
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    '*',
    'FILE:EXT:spark_dms/Configuration/FlexForms/Detail.xml',
    'sparkdms_detail'
);

