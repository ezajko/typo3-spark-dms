<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * @extends Repository<\EtfUnsa\SparkDms\Domain\Model\DocumentCategory>
 */
class DocumentCategoryRepository extends Repository
{
    protected $defaultOrderings = [
        'title' => \TYPO3\CMS\Extbase\Persistence\QueryInterface::ORDER_ASCENDING,
    ];
}
