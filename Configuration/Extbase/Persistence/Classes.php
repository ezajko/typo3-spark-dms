<?php

declare(strict_types=1);

return [
    \EtfUnsa\SparkDms\Domain\Model\Document::class => [
        'tableName' => 'tx_sparkdms_domain_model_document',
    ],
    \EtfUnsa\SparkDms\Domain\Model\DocumentType::class => [
        'tableName' => 'tx_sparkdms_domain_model_document_type',
    ],
    \EtfUnsa\SparkDms\Domain\Model\DocumentCategory::class => [
        'tableName' => 'tx_sparkdms_domain_model_document_category',
    ],
    \EtfUnsa\SparkDms\Domain\Model\DocumentVersion::class => [
        'tableName' => 'tx_sparkdms_domain_model_document_version',
        'properties' => [
            'createdAt' => [
                'fieldName' => 'created_at',
            ],
        ],
    ],
];
