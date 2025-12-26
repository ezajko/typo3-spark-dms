<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Controller\Backend;

use EtfUnsa\SparkDms\Domain\Repository\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/**
 * Backend module controller for DMS documents
 * 
 * @author Ernedin Zajko <ezajko@root.ba>
 */
class DocumentController extends ActionController
{
    public function __construct(
        protected readonly DocumentRepository $documentRepository,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly \TYPO3\CMS\Backend\Routing\UriBuilder $backendUriBuilder,
        protected readonly \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory,
        protected readonly SiteFinder $siteFinder
    ) {}

    /**
     * Initialize action - set storage PID from Site Settings
     */
    protected function initializeAction(): void
    {
        parent::initializeAction();
        
        $storagePid = $this->getStoragePid();
        
        if ($storagePid > 0) {
            $querySettings = $this->documentRepository->createQuery()->getQuerySettings();
            $querySettings->setStoragePageIds([$storagePid]);
            $querySettings->setRespectStoragePage(true);
            $this->documentRepository->setDefaultQuerySettings($querySettings);
        } else {
            $querySettings = $this->documentRepository->createQuery()->getQuerySettings();
            $querySettings->setRespectStoragePage(false);
            $this->documentRepository->setDefaultQuerySettings($querySettings);
        }
    }

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
     * List all documents
     */
    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        
        // DocHeader Buttons
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        
        $newIcon = $this->iconFactory->getIcon('actions-add', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL);
        $storagePid = $this->getStoragePid();
        
        // Link to TCA record_edit form
        $newLink = $this->backendUriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                'tx_sparkdms_domain_model_document' => [
                    $storagePid => 'new'
                ]
            ],
            'returnUrl' => (string)$this->backendUriBuilder->buildUriFromRoute('spark_dms_document')
        ]);
        
        $newButton = $buttonBar->makeLinkButton()
            ->setHref($newLink)
            ->setTitle('Create New Document')
            ->setIcon($newIcon);
        
        $buttonBar->addButton($newButton, \TYPO3\CMS\Backend\Template\Components\ButtonBar::BUTTON_POSITION_LEFT);

        // Data
        $documents = $this->documentRepository->findAll();
        $moduleTemplate->assign('documents', $documents);
        $moduleTemplate->assign('storagePid', $storagePid);

        return $moduleTemplate->renderResponse('Backend/Document/List');
    }

    /**
     * Index action (alias for list)
     */
    public function indexAction(): ResponseInterface
    {
        return $this->listAction();
    }
}
