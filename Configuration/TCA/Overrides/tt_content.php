<?php

defined('TYPO3') or die();

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'SparkDms',
    'Pi1',
    'Spark DMS: List / Download',
    'spark-dms-module'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'SparkDms',
    'Pi2',
    'Spark DMS: Detail View',
    'spark-dms-module'
);

// Add FlexForm for Pi1 (List) to configure filters
$pluginSignature = 'sparkdms_pi1';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignature] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $pluginSignature,
    'FILE:EXT:spark_dms/Configuration/FlexForms/List.xml'
);
