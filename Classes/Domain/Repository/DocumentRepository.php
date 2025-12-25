<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<\EtfUnsa\SparkDms\Domain\Model\Document>
 */
class DocumentRepository extends Repository
{
    protected $defaultOrderings = [
        'documentDate' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_DESCENDING,
        'title' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
    ];
}
