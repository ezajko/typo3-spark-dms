<?php

use RootBa\SparkDms\Controller\Backend\DocumentController;

return [
    'spark_dms_document' => [
        'parent' => 'academic',
        'access' => 'user',
        'workspaces' => '*',
        'path' => '/module/academic/spark-dms-document',
        'labels' => 'LLL:EXT:spark_dms/Resources/Private/Language/locallang_mod.xlf',
        'extensionName' => 'SparkDms',
        'controllerActions' => [
            DocumentController::class => [
                'list', 'index'
            ],
        ],
    ],
];
