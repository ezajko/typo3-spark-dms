<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Controller\Backend;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder as BackendUriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Backend module controller for DMS documents
 * 
 * Provides document listing with filtering and sorting capabilities:
 * - Search (title, registry_number)
 * - Filter by Type, Category, Date Range
 * - Sort by title, date, or modification time
 * 
 * @author Ernedin Zajko <ezajko@root.ba>
 */
class DocumentController extends ActionController
{
    // Table names
    private const TABLE_DOCUMENT = 'tx_sparkdms_domain_model_document';
    private const TABLE_TYPE = 'tx_sparkdms_domain_model_document_type';
    private const TABLE_CATEGORY = 'tx_sparkdms_domain_model_document_category';
    private const TABLE_CATEGORY_MM = 'tx_sparkdms_document_category_mm';

    public function __construct(
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly BackendUriBuilder $backendUriBuilder,
        protected readonly IconFactory $iconFactory,
        protected readonly SiteFinder $siteFinder,
        protected readonly ConnectionPool $connectionPool
    ) {}

    /**
     * Get storage PID from Site Settings
     */
    protected function getStoragePid(): int
    {
        try {
            $sites = $this->siteFinder->getAllSites();
            foreach ($sites as $site) {
                $settings = $site->getSettings();
                $storagePid = $settings->get('sparkdms.recordStoragePid', 0);
                if ($storagePid > 0) {
                    return (int)$storagePid;
                }
            }
        } catch (\Exception $e) {
            // Ignore if no site found
        }
        return 0;
    }

    /**
     * List documents with filtering and sorting
     */
    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        
        // Get filter and sort from request (POST or GET)
        $queryParams = $this->request->getQueryParams();
        $postParams = $this->request->getParsedBody() ?? [];
        
        // Filter can come from POST (form submit) or GET (sort links)
        $filter = $postParams['filter'] ?? $queryParams['filter'] ?? [];
        $sort = $queryParams['sort'] ?? 'tstamp';
        $direction = $queryParams['direction'] ?? 'desc';
        
        // Sanitize sort field
        $allowedSortFields = ['title', 'registry_number', 'document_date', 'tstamp'];
        if (!in_array($sort, $allowedSortFields, true)) {
            $sort = 'tstamp';
        }
        $direction = strtoupper($direction) === 'ASC' ? 'ASC' : 'DESC';
        
        // Storage PID
        $storagePid = $this->getStoragePid();
        
        // Build query
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_DOCUMENT);
        $queryBuilder
            ->select('d.*')
            ->from(self::TABLE_DOCUMENT, 'd')
            ->orderBy('d.' . $sort, $direction);
        
        // Storage PID constraint
        if ($storagePid > 0) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('d.pid', $queryBuilder->createNamedParameter($storagePid, Connection::PARAM_INT))
            );
        }
        
        // Hidden/Deleted constraints (standard TYPO3 fields)
        $queryBuilder->andWhere(
            $queryBuilder->expr()->eq('d.deleted', 0),
            $queryBuilder->expr()->eq('d.hidden', 0)
        );
        
        // Filter: Search (title, registry_number)
        if (!empty($filter['search'])) {
            $searchTerm = '%' . $queryBuilder->escapeLikeWildcards(trim($filter['search'])) . '%';
            $queryBuilder->andWhere(
                $queryBuilder->expr()->or(
                    $queryBuilder->expr()->like('d.title', $queryBuilder->createNamedParameter($searchTerm)),
                    $queryBuilder->expr()->like('d.registry_number', $queryBuilder->createNamedParameter($searchTerm))
                )
            );
        }
        
        // Filter: Type
        if (!empty($filter['type'])) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->eq('d.type', $queryBuilder->createNamedParameter((int)$filter['type'], Connection::PARAM_INT))
            );
        }
        
        // Filter: Category (MM relation)
        if (!empty($filter['category'])) {
            $queryBuilder
                ->join(
                    'd',
                    self::TABLE_CATEGORY_MM,
                    'mm',
                    $queryBuilder->expr()->eq('mm.uid_local', $queryBuilder->quoteIdentifier('d.uid'))
                )
                ->andWhere(
                    $queryBuilder->expr()->eq('mm.uid_foreign', $queryBuilder->createNamedParameter((int)$filter['category'], Connection::PARAM_INT))
                )
                ->groupBy('d.uid'); // Prevent duplicates from MM join
        }
        
        // Filter: Date Range
        if (!empty($filter['dateFrom'])) {
            $dateFrom = strtotime($filter['dateFrom']);
            if ($dateFrom) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->gte('d.document_date', $queryBuilder->createNamedParameter($dateFrom, Connection::PARAM_INT))
                );
            }
        }
        if (!empty($filter['dateTo'])) {
            $dateTo = strtotime($filter['dateTo'] . ' 23:59:59');
            if ($dateTo) {
                $queryBuilder->andWhere(
                    $queryBuilder->expr()->lte('d.document_date', $queryBuilder->createNamedParameter($dateTo, Connection::PARAM_INT))
                );
            }
        }
        
        // Execute query
        $documents = $queryBuilder->executeQuery()->fetchAllAssociative();
        
        // Enrich documents with related data (Type titles, Category titles)
        $documents = $this->enrichDocumentsWithRelations($documents);
        
        // Get available Types and Categories for filter dropdowns
        $types = $this->getAvailableTypes($storagePid);
        $categories = $this->getAvailableCategories($storagePid);
        
        // DocHeader Buttons
        $this->addDocHeaderButtons($moduleTemplate, $storagePid);
        
        // Assign to template
        $moduleTemplate->assign('documents', $documents);
        $moduleTemplate->assign('filter', $filter);
        $moduleTemplate->assign('sort', $sort);
        $moduleTemplate->assign('direction', $direction);
        $moduleTemplate->assign('types', $types);
        $moduleTemplate->assign('categories', $categories);
        $moduleTemplate->assign('storagePid', $storagePid);

        return $moduleTemplate->renderResponse('Backend/Document/List');
    }

    /**
     * Enrich documents array with Type and Category titles
     */
    protected function enrichDocumentsWithRelations(array $documents): array
    {
        if (empty($documents)) {
            return $documents;
        }
        
        // Collect UIDs
        $documentUids = array_column($documents, 'uid');
        $typeUids = array_filter(array_unique(array_column($documents, 'type')));
        
        // Fetch Types
        $typeTitles = [];
        if (!empty($typeUids)) {
            $qb = $this->connectionPool->getQueryBuilderForTable(self::TABLE_TYPE);
            $result = $qb->select('uid', 'title')
                ->from(self::TABLE_TYPE)
                ->where($qb->expr()->in('uid', $typeUids))
                ->executeQuery()
                ->fetchAllAssociative();
            foreach ($result as $row) {
                $typeTitles[$row['uid']] = $row['title'];
            }
        }
        
        // Fetch Categories via MM
        $categoryMap = [];
        $qb = $this->connectionPool->getQueryBuilderForTable(self::TABLE_CATEGORY_MM);
        $mmResult = $qb->select('mm.uid_local', 'c.title')
            ->from(self::TABLE_CATEGORY_MM, 'mm')
            ->join('mm', self::TABLE_CATEGORY, 'c', $qb->expr()->eq('c.uid', $qb->quoteIdentifier('mm.uid_foreign')))
            ->where($qb->expr()->in('mm.uid_local', $documentUids))
            ->executeQuery()
            ->fetchAllAssociative();
        foreach ($mmResult as $row) {
            $categoryMap[$row['uid_local']][] = $row['title'];
        }
        
        // Enrich documents
        foreach ($documents as &$doc) {
            $doc['type_title'] = $typeTitles[$doc['type']] ?? '';
            $doc['category_titles'] = $categoryMap[$doc['uid']] ?? [];
        }
        
        return $documents;
    }

    /**
     * Get available document types for filter dropdown
     */
    protected function getAvailableTypes(int $storagePid): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable(self::TABLE_TYPE);
        $qb->select('uid', 'title')
            ->from(self::TABLE_TYPE)
            ->where($qb->expr()->eq('deleted', 0))
            ->orderBy('title', 'ASC');
        
        if ($storagePid > 0) {
            $qb->andWhere($qb->expr()->eq('pid', $storagePid));
        }
        
        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Get available document categories for filter dropdown
     */
    protected function getAvailableCategories(int $storagePid): array
    {
        $qb = $this->connectionPool->getQueryBuilderForTable(self::TABLE_CATEGORY);
        $qb->select('uid', 'title')
            ->from(self::TABLE_CATEGORY)
            ->where($qb->expr()->eq('deleted', 0))
            ->orderBy('title', 'ASC');
        
        if ($storagePid > 0) {
            $qb->andWhere($qb->expr()->eq('pid', $storagePid));
        }
        
        return $qb->executeQuery()->fetchAllAssociative();
    }

    /**
     * Add DocHeader buttons (New Document)
     */
    protected function addDocHeaderButtons($moduleTemplate, int $storagePid): void
    {
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        
        $newIcon = $this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL);
        
        $newLink = $this->backendUriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                self::TABLE_DOCUMENT => [
                    $storagePid => 'new'
                ]
            ],
            'returnUrl' => (string)$this->backendUriBuilder->buildUriFromRoute('spark_dms_document')
        ]);
        
        $newButton = $buttonBar->makeLinkButton()
            ->setHref($newLink)
            ->setTitle('Create New Document')
            ->setIcon($newIcon);
        
        $buttonBar->addButton($newButton, ButtonBar::BUTTON_POSITION_LEFT);
    }

    /**
     * Index action (alias for list)
     */
    public function indexAction(): ResponseInterface
    {
        return $this->listAction();
    }
}
