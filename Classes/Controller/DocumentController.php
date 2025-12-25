<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Controller;

use EtfUnsa\SparkDms\Domain\Model\Document;
use EtfUnsa\SparkDms\Domain\Model\Dto\DocumentDemand;
use EtfUnsa\SparkDms\Domain\Repository\DocumentRepository;
use EtfUnsa\SparkDms\Service\DocumentUrlService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Persistence\Exception\InvalidQueryException;

class DocumentController extends ActionController
{
    public function __construct(
        protected readonly DocumentRepository $documentRepository,
        protected readonly DocumentUrlService $documentUrlService,
        protected readonly ResponseFactoryInterface $httpResponseFactory,
        protected readonly StreamFactoryInterface $httpStreamFactory,
        protected readonly Context $context
    ) {}


    /**
     * @param DocumentDemand|null $demand
     */
    public function listAction(?DocumentDemand $demand = null): ResponseInterface
    {
        if ($demand === null) {
            $demand = new DocumentDemand();
        }
        
        // Map arguments to DTO if Extbase didn't (simplified)
        // Usually Extbase handles this via $this->request->getArguments() -> property mapping
        
        // $documents = $this->documentRepository->findByDemand($demand);
        // Using findAll for now until Repository is updated
        $documents = $this->documentRepository->findAll();

        $this->view->assign('documents', $documents);
        $this->view->assign('demand', $demand);
        $this->view->assign('urlService', $this->documentUrlService); // Pass service to view if needed

        return $this->htmlResponse();
    }

    public function showAction(Document $document): ResponseInterface
    {
        $this->view->assign('document', $document);
        $this->view->assign('downloadUrl', $this->documentUrlService->generateDownloadUrl($this->uriBuilder, $document));
        return $this->htmlResponse();
    }

    public function downloadAction(Document $document): ResponseInterface
    {
        // 1. Check Access
        if ($document->isProtected()) {
            if (!$this->context->getPropertyFromAspect('frontend', 'isUserLoggedIn')) {
                 return $this->httpResponseFactory->createResponse(403)
                     ->withBody($this->httpStreamFactory->createStream('Access Denied: Login required to view this protected document.'));
            }
        }

        // 2. Get File (Latest Version)
        $fileReference = $document->getFile();
        if ($fileReference === null) {
            return $this->httpResponseFactory->createResponse(404)
                ->withBody($this->httpStreamFactory->createStream('File not found for this document.'));
        }

        $originalFile = $fileReference->getOriginalResource();
        
        // 3. Prepare Dynamic Filename
        $filename = $originalFile->getName(); // Default
        // Logic: {Title}.{Version}.{ext}
        $latestVersion = $document->getLatestVersion();
        if ($latestVersion) {
            $cleanTitle = preg_replace('/[^a-zA-Z0-9_-]/', '_', $document->getTitle());
            $cleanVersion = preg_replace('/[^a-zA-Z0-9\.]/', '', $latestVersion->getVersionLabel());
            $extension = $originalFile->getExtension();
            $filename = sprintf('%s.%s.%s', $cleanTitle, $cleanVersion, $extension);
        }

        // 4. Serve Content (Stream)
        $stream = $originalFile->getStream();
        
        return $this->httpResponseFactory->createResponse()
            ->withHeader('Content-Type', $originalFile->MimeType)
            ->withHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->withHeader('Content-Length', (string)$originalFile->getSize())
            ->withBody($this->httpStreamFactory->createStreamFromResource($stream));

    }
}
