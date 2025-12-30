<?php

defined('TYPO3') or die();

(function () {
    // Plugin Pi1: List (and Download)
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
        'SparkDms',
        'Pi1',
        [
            \EtfUnsa\SparkDms\Controller\DocumentController::class => 'list, show, download'
        ],
        // non-cacheable actions (list must be non-cached for filter/pagination to work)
        [
            \EtfUnsa\SparkDms\Controller\DocumentController::class => 'list, download'
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

    // DataHandler Hook for file organization after Document save
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['spark_dms'] 
        = \EtfUnsa\SparkDms\Hook\DataHandlerHook::class;

})();
