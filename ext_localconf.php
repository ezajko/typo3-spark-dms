<?php

defined('TYPO3') or die();

(function () {
    // Plugin Pi1: List (and Download)
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'SparkDms',
        'Pi1',
        [
            \EtfUnsa\SparkDms\Controller\DocumentController::class => 'list, download'
        ],
        // non-cacheable actions
        [
            \EtfUnsa\SparkDms\Controller\DocumentController::class => 'download'
        ]
    );

    // Plugin Pi2: Show (Detail)
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'SparkDms',
        'Pi2',
        [
            \EtfUnsa\SparkDms\Controller\DocumentController::class => 'show, download'
        ],
        // non-cacheable actions
        [
            \EtfUnsa\SparkDms\Controller\DocumentController::class => 'download'
        ]
    );



    // Register PageTSConfig for Backend Module View
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        'module.tx_sparkdms {
            view {
                templateRootPaths.0 = EXT:spark_dms/Resources/Private/Templates/
                partialRootPaths.0 = EXT:spark_dms/Resources/Private/Partials/
                layoutRootPaths.0 = EXT:spark_dms/Resources/Private/Layouts/
            }
        }'
    );

})();

