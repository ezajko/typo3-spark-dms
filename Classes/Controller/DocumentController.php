<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Controller;

use EtfUnsa\SparkDms\Domain\Model\Document;
use EtfUnsa\SparkDms\Domain\Model\DocumentVersion;
use EtfUnsa\SparkDms\Domain\Model\Dto\DocumentDemand;
use EtfUnsa\SparkDms\Domain\Repository\DocumentRepository;
use EtfUnsa\SparkDms\Domain\Repository\DocumentVersionRepository;
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
        protected readonly DocumentUrlService $documentUrlService,
        protected readonly ResponseFactoryInterface $httpResponseFactory,
        protected readonly StreamFactoryInterface $httpStreamFactory,
        protected readonly Context $context
    ) {}


    /**
     * List documents with filtering from FlexForm settings
     */
    public function listAction(int $currentPage = 1): ResponseInterface
    {
        // Build demand from FlexForm settings
        $demand = new DocumentDemand();
        
        // Items per page
        $itemsPerPage = (int)($this->settings['itemsPerPage'] ?? 10);
        if ($itemsPerPage < 1) {
            $itemsPerPage = 10;
        }

        // Apply total limit from settings (optional)
        $limit = (int)($this->settings['limit'] ?? 0);
        if ($limit > 0) {
            $demand->setLimit($limit);
        }
        
        // Apply date filters
        $dateFrom = $this->settings['dateFrom'] ?? 0;
        if ($dateFrom > 0) {
            $demand->setDateFrom(new \DateTime('@' . $dateFrom));
        }
        
        $dateTo = $this->settings['dateTo'] ?? 0;
        if ($dateTo > 0) {
            $demand->setDateTo(new \DateTime('@' . $dateTo));
        }
        
        // Load documents using demand (QueryResult)
        $documents = $this->documentRepository->findByDemand($demand);
        
        // Create Pagination
        $paginator = new QueryResultPaginator($documents, $currentPage, $itemsPerPage);
        $pagination = new SlidingWindowPagination($paginator, 5); // 5 pages in window

        // Get detailPid from settings for show links
        $detailPid = (int)($this->settings['detailPid'] ?? 0);

        $this->view->assign('documents', $documents); // Re-assign for debug/fallback
        $this->view->assign('pagination', $pagination);
        $this->view->assign('paginator', $paginator);
        $this->view->assign('demand', $demand);
        $this->view->assign('detailPid', $detailPid);
        $this->view->assign('urlService', $this->documentUrlService);

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
