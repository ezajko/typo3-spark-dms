<?php

declare(strict_types=1);

namespace RootBa\SparkDms\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Resource\Event\AfterDefaultUploadFolderWasResolvedEvent;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Redirect DMS file uploads to protected storage /_inbox folder
 * 
 * Uses getTable() and getFieldName() to check if upload is for DMS tables.
 * 
 * @author Ernedin Zajko <ezajko@root.ba>
 */
#[AsEventListener(
    identifier: 'spark-dms/default-upload-folder',
    event: AfterDefaultUploadFolderWasResolvedEvent::class
)]
final class DefaultUploadFolderListener
{
    protected const DMS_TABLES = [
        'tx_sparkdms_domain_model_document_version',
    ];

    public function __invoke(AfterDefaultUploadFolderWasResolvedEvent $event): void
    {
        // Check if we're dealing with a DMS table
        $tableName = $event->getTable();
        
        if ($tableName === null || !in_array($tableName, self::DMS_TABLES, true)) {
            return; // Not a DMS table, skip
        }

        // Get protected storage UID from Site Settings
        $protectedStorageUid = $this->getProtectedStorageUid();
        
        if ($protectedStorageUid <= 0) {
            return; // No protected storage configured
        }

        try {
            // Get the storage
            $storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
            $storage = $storageRepository->findByUid($protectedStorageUid);
            
            if (!$storage) {
                return; // Storage not found
            }

            // Ensure _inbox folder exists
            $inboxPath = '/_inbox/';
            if (!$storage->hasFolder($inboxPath)) {
                $storage->createFolder('_inbox', $storage->getRootLevelFolder());
            }

            $inboxFolder = $storage->getFolder($inboxPath);
            
            // Set the upload folder
            $event->setUploadFolder($inboxFolder);

        } catch (\Exception $e) {
            // Log error but don't break upload
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)
                ->getLogger(__CLASS__)
                ->error('Failed to set DMS upload folder', [
                    'exception' => $e->getMessage(),
                    'table' => $tableName,
                ]);
        }
    }

    /**
     * Get protected storage UID from Site Settings
     */
    protected function getProtectedStorageUid(): int
    {
        try {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $sites = $siteFinder->getAllSites();
            
            foreach ($sites as $site) {
                $settings = $site->getSettings();
                $storageUid = $settings->get('sparkdms.protectedStorageUid', 0);
                if ($storageUid > 0) {
                    return (int)$storageUid;
                }
            }
        } catch (\Exception $e) {
            GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)
                ->getLogger(__CLASS__)
                ->warning('Could not get protected storage UID', ['exception' => $e->getMessage()]);
        }
        
        return 0;
    }
}
