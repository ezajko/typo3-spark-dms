<?php

use EtfUnsa\SparkDms\Controller\Backend\DocumentController;

return [
    'sparkdms_web_sparkdms' => [
        'parent' => 'web',
        'position' => ['after' => 'web_info'],
        'access' => 'user,group',
        'workspaces' => 'live',
        'path' => '/module/web/sparkdms',
        'labels' => 'LLL:EXT:spark_dms/Resources/Private/Language/locallang_mod.xlf',
        'iconIdentifier' => 'module-filelist',
        'extensionName' => 'SparkDms',

        'controllerActions' => [
            DocumentController::class => [
                'list',
                'index', 
            ],
        ],
    ],
];

