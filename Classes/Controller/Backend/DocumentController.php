<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Controller\Backend;

use EtfUnsa\SparkDms\Domain\Repository\DocumentRepository;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

class DocumentController extends ActionController

{
    public function __construct(
        protected readonly DocumentRepository $documentRepository,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly \TYPO3\CMS\Backend\Routing\UriBuilder $backendUriBuilder,
        protected readonly \TYPO3\CMS\Core\Imaging\IconFactory $iconFactory
    ) {}



    public function listAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        
        // 1. Add DocHeader Buttons
        $buttonBar = $moduleTemplate->getDocHeaderComponent()->getButtonBar();
        
        // Create "New Document" Button
        $newIcon = $this->iconFactory->getIcon('actions-add', \TYPO3\CMS\Core\Imaging\Icon::SIZE_SMALL);

        
        $newLink = $this->backendUriBuilder->buildUriFromRoute('record_edit', [
            'edit' => [
                'tx_sparkdms_domain_model_document' => [
                    0 => 'new'
                ]
            ],
            'returnUrl' => (string)$this->backendUriBuilder->buildUriFromRoute('sparkdms_web_sparkdms')
        ]);
        
        $newButton = $buttonBar->makeLinkButton()
            ->setHref($newLink)
            ->setTitle('Create New Document')
            ->setIcon($newIcon);
        
        $buttonBar->addButton($newButton, \TYPO3\CMS\Backend\Template\Components\ButtonBar::BUTTON_POSITION_LEFT);

        // 2. Data
        $documents = $this->documentRepository->findAll();
        $moduleTemplate->assign('documents', $documents);

        return $moduleTemplate->renderResponse('Backend/Document/List');
    }



    public function indexAction(): ResponseInterface
    {
        return $this->listAction();
    }
}
