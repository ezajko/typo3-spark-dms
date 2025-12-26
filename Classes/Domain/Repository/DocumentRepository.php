<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Domain\Repository;

use EtfUnsa\SparkDms\Domain\Model\Dto\DocumentDemand;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;
use TYPO3\CMS\Extbase\Persistence\QueryResultInterface;
use TYPO3\CMS\Extbase\Persistence\Repository;

/**
 * Repository for Document entities with demand-based filtering
 * 
 * @extends Repository<\EtfUnsa\SparkDms\Domain\Model\Document>
 * @author Ernedin Zajko <ezajko@root.ba>
 */
class DocumentRepository extends Repository
{
    protected $defaultOrderings = [
        'documentDate' => QueryInterface::ORDER_DESCENDING,
        'title' => QueryInterface::ORDER_ASCENDING,
    ];

    /**
     * Initialize repository - disable storagePid restriction if not configured
     * This allows finding records from any page, which is typical for DMS systems
     */
    public function initializeObject(): void
    {
        $querySettings = $this->createQuery()->getQuerySettings();
        $querySettings->setRespectStoragePage(false);
        $this->setDefaultQuerySettings($querySettings);
    }

    /**
     * Find documents by demand (with filtering)
     * 
     * @param DocumentDemand $demand Filter criteria
     * @return QueryResultInterface<\EtfUnsa\SparkDms\Domain\Model\Document>
     */
    public function findByDemand(DocumentDemand $demand): QueryResultInterface
    {
        $query = $this->createQuery();
        $constraints = [];

        // Search filter (title)
        if (!empty($demand->getSearch())) {
            $searchTerm = '%' . $demand->getSearch() . '%';
            $constraints[] = $query->like('title', $searchTerm);
        }

        // Type filter
        if ($demand->getType() !== null) {
            $constraints[] = $query->equals('type', $demand->getType());
        }

        // Category filter (MM relation)
        if ($demand->getCategory() !== null) {
            $constraints[] = $query->contains('category', $demand->getCategory());
        }

        // Date range: From
        if ($demand->getDateFrom() !== null) {
            $constraints[] = $query->greaterThanOrEqual('documentDate', $demand->getDateFrom());
        }

        // Date range: To
        if ($demand->getDateTo() !== null) {
            $constraints[] = $query->lessThanOrEqual('documentDate', $demand->getDateTo());
        }

        // Apply constraints
        if (!empty($constraints)) {
            $query->matching($query->logicalAnd(...$constraints));
        }

        // Ordering
        $orderings = $demand->getOrdering();
        if (!empty($orderings)) {
            $queryOrderings = [];
            foreach ($orderings as $field => $direction) {
                $queryOrderings[$field] = strtoupper($direction) === 'ASC' 
                    ? QueryInterface::ORDER_ASCENDING 
                    : QueryInterface::ORDER_DESCENDING;
            }
            $query->setOrderings($queryOrderings);
        }

        // Limit
        if ($demand->getLimit() > 0) {
            $query->setLimit($demand->getLimit());
        }

        // Offset (for pagination)
        if ($demand->getOffset() > 0) {
            $query->setOffset($demand->getOffset());
        }

        return $query->execute();
    }

    /**
     * Find documents by multiple type UIDs
     * 
     * @param array<int> $typeUids
     * @return QueryResultInterface<\EtfUnsa\SparkDms\Domain\Model\Document>
     */
    public function findByTypeUids(array $typeUids): QueryResultInterface
    {
        $query = $this->createQuery();
        
        if (!empty($typeUids)) {
            $query->matching($query->in('type', $typeUids));
        }

        return $query->execute();
    }

    /**
     * Find documents by multiple category UIDs (any match)
     * 
     * @param array<int> $categoryUids
     * @return QueryResultInterface<\EtfUnsa\SparkDms\Domain\Model\Document>
     */
    public function findByCategoryUids(array $categoryUids): QueryResultInterface
    {
        $query = $this->createQuery();
        
        if (!empty($categoryUids)) {
            $categoryConstraints = [];
            foreach ($categoryUids as $categoryUid) {
                $categoryConstraints[] = $query->contains('category', $categoryUid);
            }
            $query->matching($query->logicalOr(...$categoryConstraints));
        }

        return $query->execute();
    }
}
