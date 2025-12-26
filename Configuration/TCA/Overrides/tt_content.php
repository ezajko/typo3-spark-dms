<?php
/**
 * TCA Overrides for tt_content
 * Registers Spark DMS plugins and their FlexForms.
 *
 * Author: Ernedin Zajko <ezajko@root.ba>
 */

defined('TYPO3') or die();

// Register Pi1: List / Download
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'SparkDms',
    'Pi1',
    'Spark DMS: List / Download',
    'spark-dms-module'
);

// Register Pi2: Detail View
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'SparkDms',
    'Pi2',
    'Spark DMS: Detail View',
    'spark-dms-module'
);

// Add FlexForm for Pi1 (List)
$pluginSignaturePi1 = 'sparkdms_pi1';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignaturePi1] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $pluginSignaturePi1,
    'FILE:EXT:spark_dms/Configuration/FlexForms/List.xml'
);

// Add FlexForm for Pi2 (Detail)
$pluginSignaturePi2 = 'sparkdms_pi2';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist'][$pluginSignaturePi2] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    $pluginSignaturePi2,
    'FILE:EXT:spark_dms/Configuration/FlexForms/Detail.xml'
);
