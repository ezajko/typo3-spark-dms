<?php

declare(strict_types=1);

return [
    \RootBa\SparkDms\Domain\Model\Document::class => [
        'tableName' => 'tx_sparkdms_domain_model_document',
    ],
    \RootBa\SparkDms\Domain\Model\DocumentType::class => [
        'tableName' => 'tx_sparkdms_domain_model_document_type',
    ],
    \RootBa\SparkDms\Domain\Model\DocumentCategory::class => [
        'tableName' => 'tx_sparkdms_domain_model_document_category',
    ],
    \RootBa\SparkDms\Domain\Model\DocumentVersion::class => [
        'tableName' => 'tx_sparkdms_domain_model_document_version',
        'properties' => [
            'createdAt' => [
                'fieldName' => 'created_at',
            ],
        ],
    ],
];
