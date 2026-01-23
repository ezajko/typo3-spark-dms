<?php

defined('TYPO3') or die();

(function () {
    // Plugin Pi1: List (and Download)
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'SparkDms',
        'Pi1',
        [
            \RootBa\SparkDms\Controller\DocumentController::class => 'list, show, download'
        ],
        // non-cacheable actions (list must be non-cached for filter/pagination to work)
        [
            \RootBa\SparkDms\Controller\DocumentController::class => 'list, download'
        ]
    );

    // Plugin Pi2: Show (Detail)
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'SparkDms',
        'Pi2',
        [
            \RootBa\SparkDms\Controller\DocumentController::class => 'show, download'
        ],
        // non-cacheable actions
        [
            \RootBa\SparkDms\Controller\DocumentController::class => 'download'
        ]
    );

    // DataHandler Hook for file organization after Document save
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['spark_dms'] 
        = \RootBa\SparkDms\Hook\DataHandlerHook::class;

    // Register Icons
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'ext-spark_dms-wizard-list',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:spark_dms/Resources/Public/Icons/Extension.svg']
    );
    $iconRegistry->registerIcon(
        'ext-spark_dms-wizard-detail',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        ['source' => 'EXT:spark_dms/Resources/Public/Icons/Document.svg']
    );

})();
