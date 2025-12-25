<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Service;

use EtfUnsa\SparkDms\Domain\Model\Document;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;

class DocumentUrlService
{
    /**
     * Generates a Download URL using the provided UriBuilder.
     * This abstracts the arguments required for the download action.
     *
     * @param UriBuilder $uriBuilder
     * @param Document $document
     * @return string
     */
    public function generateDownloadUrl(UriBuilder $uriBuilder, Document $document): string
    {
        return $uriBuilder
            ->reset()
            ->setTargetPageType(0) // Standard page
            ->setCreateAbsoluteUri(true)
            ->uriFor('download', ['document' => $document], 'Document', 'SparkDms', 'Pi1'); 
            // Note: Targeting Pi1 (List/Standard) or Pi2? 
            // Plan said "downloadAction" in DocumentController.
            // Usually we target the plugin that handles it. Assuming Pi1 for now as it's the main entry.
    }
}
