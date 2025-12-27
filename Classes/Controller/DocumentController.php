<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Controller;

use EtfUnsa\SparkDms\Domain\Model\Document;
use EtfUnsa\SparkDms\Domain\Model\DocumentVersion;
use EtfUnsa\SparkDms\Domain\Model\Dto\DocumentDemand;
use EtfUnsa\SparkDms\Domain\Repository\DocumentRepository;
use EtfUnsa\SparkDms\Domain\Repository\DocumentVersionRepository;
use EtfUnsa\SparkDms\Domain\Repository\DocumentCategoryRepository;
use EtfUnsa\SparkDms\Domain\Repository\DocumentTypeRepository;
use EtfUnsa\SparkDms\Service\DocumentUrlService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\PropagateResponseException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Core\Pagination\SlidingWindowPagination;
use TYPO3\CMS\Extbase\Pagination\QueryResultPaginator;

class DocumentController extends ActionController
{
    public function __construct(
        protected readonly DocumentRepository $documentRepository,
        protected readonly DocumentVersionRepository $documentVersionRepository,
        protected readonly DocumentCategoryRepository $documentCategoryRepository,
        protected readonly DocumentTypeRepository $documentTypeRepository,
        protected readonly DocumentUrlService $documentUrlService,
        protected readonly ResponseFactoryInterface $httpResponseFactory,
        protected readonly StreamFactoryInterface $httpStreamFactory,
        protected readonly Context $context
    ) {}


    /**
     * List documents with filtering from FlexForm settings and Frontend Filters
     * 
     * @param int $currentPage
     * @param array|null $filter User-submitted filters from frontend
     */
    public function listAction(int $currentPage = 1, ?array $filter = null): ResponseInterface
    {
        // Settings Structure (FlexForm mapped):
        // - settings.view.viewType          (string: Default, Simple, Card, Debug)
        // - settings.view.frontEndFilter    (bool: 0|1)
        // - settings.view.detailPid         (int: page UID)
        // - settings.view.pagination.limit  (int: max total items, 0=unlimited)
        // - settings.view.pagination.itemsPerPage (int: items per page)
        // - settings.selectedDocuments      (csv: document UIDs)
        // - settings.filter.documentTypes   (csv: type UIDs)
        // - settings.filter.documentCategories (csv: category UIDs)
        // - settings.filter.dateRange.dateFrom (timestamp)
        // - settings.filter.dateRange.dateTo   (timestamp)
        //
        // NOTE: Legacy flat keys (e.g. settings.limit, settings.detailPid, settings.viewType)
        // may exist in database for old content elements. We only use the nested structure above.

        // 1. Initialize Demand
        $demand = new DocumentDemand();
        
        // 2. Read View and Pagination Settings
        $viewSettings = $this->settings['view'] ?? [];
        $paginationSettings = $viewSettings['pagination'] ?? [];
        
        // Items per page
        $itemsPerPage = (int)($paginationSettings['itemsPerPage'] ?? 10);
        if ($itemsPerPage < 1) {
            $itemsPerPage = 10;
        }

        // Apply total limit from settings (optional)
        $limit = (int)($paginationSettings['limit'] ?? 0);
        if ($limit > 0) {
            $demand->setLimit($limit);
        }
        
        // 3. Read FlexForm Constraints (settings.filter.*)
        $filterSettings = $this->settings['filter'] ?? [];
        
        // Allowed Types (Constraints)
        $allowedTypeUids = [];
        $documentTypesStr = $filterSettings['documentTypes'] ?? '';
        if (!empty($documentTypesStr)) {
            $allowedTypeUids = array_filter(array_map('intval', explode(',', $documentTypesStr)));
            $demand->setAllowedTypes($allowedTypeUids);
        }
        
        // Allowed Categories (Constraints)
        $allowedCategoryUids = [];
        $documentCategoriesStr = $filterSettings['documentCategories'] ?? '';
        if (!empty($documentCategoriesStr)) {
            $allowedCategoryUids = array_filter(array_map('intval', explode(',', $documentCategoriesStr)));
            $demand->setAllowedCategories($allowedCategoryUids);
        }
        
        // FlexForm Date Range
        $dateRangeSettings = $filterSettings['dateRange'] ?? [];
        $flexDateFrom = (int)($dateRangeSettings['dateFrom'] ?? 0);
        $flexDateTo = (int)($dateRangeSettings['dateTo'] ?? 0);

        // 3. Process Frontend Filters and Merge (Intersection Logic)
        if ($filter !== null) {
            // Keyword Search
            if (!empty($filter['search'])) {
                $demand->setSearch((string)$filter['search']);
            }

            // Category Filter (must be in allowedCategories if constrained)
            if (!empty($filter['category'])) {
                $filterCategoryUid = (int)$filter['category'];
                if (empty($allowedCategoryUids) || in_array($filterCategoryUid, $allowedCategoryUids, true)) {
                    $category = $this->documentCategoryRepository->findByUid($filterCategoryUid);
                    if ($category) {
                        $demand->setCategory($category);
                    }
                } else {
                    // Logic: Empty list if selected category is not allowed
                    // We can achieve this by forcing a dummy constraint or just letting it fail mapping
                    // For now, we set a category that's not allowed which will yield 0 results due to repo logic
                }
            }

            // Type Filter (must be in allowedTypes if constrained)
            if (!empty($filter['type'])) {
                $filterTypeUid = (int)$filter['type'];
                if (empty($allowedTypeUids) || in_array($filterTypeUid, $allowedTypeUids, true)) {
                    $type = $this->documentTypeRepository->findByUid($filterTypeUid);
                    if ($type) {
                        $demand->setType($type);
                    }
                }
            }

            // Date Range (Intersection)
            // Note: If user selects a year, it could be handled here too.
            // For now, we support dateFrom/dateTo if sent from frontend.
            if (!empty($filter['dateFrom'])) {
                $userDateFrom = strtotime($filter['dateFrom']);
                $finalFrom = ($userDateFrom > $flexDateFrom) ? $userDateFrom : $flexDateFrom;
            } else {
                $finalFrom = $flexDateFrom;
            }

            if (!empty($filter['dateTo'])) {
                $userDateTo = strtotime($filter['dateTo']);
                if ($flexDateTo > 0) {
                    $finalTo = ($userDateTo < $flexDateTo) ? $userDateTo : $flexDateTo;
                } else {
                    $finalTo = $userDateTo;
                }
            } else {
                $finalTo = $flexDateTo;
            }

            // Year Filter Override (Intersection with Year)
            if (!empty($filter['year'])) {
                $selectedYear = (int)$filter['year'];
                $yearStart = mktime(0, 0, 0, 1, 1, $selectedYear);
                $yearEnd = mktime(23, 59, 59, 12, 31, $selectedYear);

                // Intersect yearStart with finalFrom
                $finalFrom = max($finalFrom, $yearStart);
                
                // Intersect yearEnd with finalTo
                if ($finalTo > 0) {
                    $finalTo = min($finalTo, $yearEnd);
                } else {
                    $finalTo = $yearEnd;
                }
            }
        } else {
            $finalFrom = $flexDateFrom;
            $finalTo = $flexDateTo;
        }

        // Set final dates in demand
        if ($finalFrom > 0) {
            $demand->setDateFrom(new \DateTime('@' . $finalFrom));
        }
        if ($finalTo > 0) {
            $demand->setDateTo(new \DateTime('@' . $finalTo));
        }
        
        // 4. Load documents using demand (QueryResult)
        $documents = $this->documentRepository->findByDemand($demand);
        
        // Create Pagination
        $paginator = new QueryResultPaginator($documents, $currentPage, $itemsPerPage);
        $pagination = new SlidingWindowPagination($paginator, 5); // 5 pages in window

        // Get detailPid from settings for show links
        $detailPid = (int)($viewSettings['detailPid'] ?? 0);

        $this->view->assign('documents', $documents); // Re-assign for debug/fallback
        $this->view->assign('pagination', $pagination);
        $this->view->assign('paginator', $paginator);
        $this->view->assign('demand', $demand);
        $this->view->assign('filter', $filter); // Pass back user filters for form persistence
        $this->view->assign('detailPid', $detailPid);
        $this->view->assign('urlService', $this->documentUrlService);
        
        // Frontend Filter: Pass available categories and types (if filter is enabled)
        if (!empty($viewSettings['frontEndFilter'])) {
            // Get available categories (either all or restricted by FlexForm)
            if (!empty($allowedCategoryUids)) {
                $availableCategories = $this->documentCategoryRepository->findBy(['uid' => $allowedCategoryUids]);
            } else {
                $availableCategories = $this->documentCategoryRepository->findAll();
            }
            
            // Get available types (either all or restricted by FlexForm)
            if (!empty($allowedTypeUids)) {
                $availableTypes = $this->documentTypeRepository->findBy(['uid' => $allowedTypeUids]);
            } else {
                $availableTypes = $this->documentTypeRepository->findAll();
            }
            
            $this->view->assign('availableCategories', $availableCategories);
            $this->view->assign('availableTypes', $availableTypes);
            $this->view->assign('availableYears', $this->documentRepository->findAllYears());
        }

        return $this->htmlResponse();
    }

    public function showAction(?Document $document = null): ResponseInterface
    {
        if ($document === null) {
            // Document not found or not provided
            return $this->htmlResponse();
        }

        $this->view->assign('document', $document);
        
        // Fetch versions via repository (sorted by crdate DESC)
        $sortedVersions = $this->documentVersionRepository->findByDocumentUid($document->getUid());
        $this->view->assign('sortedVersions', $sortedVersions);
        
        $this->view->assign('downloadUrl', $this->documentUrlService->generateDownloadUrl($this->uriBuilder, $document));
        return $this->htmlResponse();
    }

    /**
     * Download document file
     * 
     * @param Document $document
     * @param DocumentVersion|null $version Optional specific version
     */
    public function downloadAction(Document $document, ?DocumentVersion $version = null): ResponseInterface
    {
        // 1. Check Access
        if ($document->getIsProtected()) {
            if (!$this->context->getPropertyFromAspect('frontend', 'isUserLoggedIn')) {
                 return $this->httpResponseFactory->createResponse(403)
                     ->withBody($this->httpStreamFactory->createStream('Access Denied: Login required to view this protected document.'));
            }
        }

        // 2. Determine File and Date
        $fileReference = null;
        $fileDate = null;
        
        if ($version) {
            $fileReference = $version->getFile();
            $fileDate = $version->getCreatedAt();
        } else {
            // Default to latest version (from relation) or document file
            // First try to find latest version object
            $latestVersion = $this->documentVersionRepository->findByDocumentUid($document->getUid())->getFirst();
            if ($latestVersion) {
                $fileReference = $latestVersion->getFile();
                $fileDate = $latestVersion->getCreatedAt();
            } else {
                // Fallback to main document file if no versions exist (legacy/simple)
                $fileReference = $document->getFile();
                $fileDate = $document->getDocumentDate();
            }
        }

        if ($fileReference === null) {
            return $this->httpResponseFactory->createResponse(404)
                ->withBody($this->httpStreamFactory->createStream('File not found for this document.'));
        }

        $originalFile = $fileReference->getOriginalResource()->getOriginalFile();
        
        $originalFile = $fileReference->getOriginalResource()->getOriginalFile();
        
        // 3. Prepare Dynamic Filename: {title}.{date}.{ext}
        // Cleanup title: slug-like style
        $cleanTitle = preg_replace('/[^a-zA-Z0-9_-]/', '-', $document->getTitle());
        $cleanTitle = preg_replace('/-+/', '-', $cleanTitle);
        $cleanTitle = strtolower(trim($cleanTitle, '-'));
        
        // Date format: Y-m-d-His (e.g., 2025-12-26-150530)
        if ($fileDate instanceof \DateTimeInterface) {
            $dateString = $fileDate->format('Y-m-d-His');
        } elseif (is_numeric($fileDate)) {
            $dateString = date('Y-m-d-His', (int)$fileDate);
        } else {
            $dateString = date('Y-m-d-His');
        }        
        $extension = $originalFile->getExtension();
        
        $filename = sprintf('%s.%s.%s', $cleanTitle, $dateString, $extension);

        // 4. Serve Content
        $contents = $originalFile->getContents();
        
        $response = $this->httpResponseFactory->createResponse()
            ->withHeader('Content-Type', $originalFile->getMimeType())
            ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string)$originalFile->getSize())
            ->withBody($this->httpStreamFactory->createStream($contents));

        throw new PropagateResponseException($response);
    }
}
