<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for DocumentType entities
 * 
 * @extends Repository<\EtfUnsa\SparkDms\Domain\Model\DocumentType>
 * @author Ernedin Zajko <ezajko@root.ba>
 */
class DocumentTypeRepository extends Repository
{
    protected $defaultOrderings = [
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Initialize repository - disable storagePid restriction
     */
    public function initializeObject(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }
}
