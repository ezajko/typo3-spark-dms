<?php

declare(strict_types=1);

namespace RootBa\SparkDms\Domain\Repository;

use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for DocumentVersion entities
 * 
 * @extends Repository<\RootBa\SparkDms\Domain\Model\DocumentVersion>
 * @author Ernedin Zajko <ezajko@root.ba>
 */
class DocumentVersionRepository extends Repository
{
    protected $defaultOrderings = [
        'createdAt' => QueryInterface::ORDER_DESCENDING,
    ];

    /**
     * Find versions by document UID, sorted by createdAt descending
     */
    public function findByDocumentUid(int $documentUid): QueryResultInterface
    {
        $query = $this->createQuery();
        $query->getQuerySettings()->setRespectStoragePage(false);
        
        $query->matching(
            $query->equals('document', $documentUid)
        );
        
        $query->setOrderings([
            'createdAt' => QueryInterface::ORDER_DESCENDING
        ]);
        
        return $query->execute();
    }
}
