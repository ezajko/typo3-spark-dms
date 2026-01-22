<?php

declare(strict_types=1);

namespace RootBa\SparkDms\Hook;

use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * DataHandler hook for organizing DMS files after Document save
 * 
 * When a Document is saved, this hook organizes all related version files
 * from _inbox/ to their final structured location.
 * 
 * @author Ernedin Zajko <ezajko@root.ba>
 */
class DataHandlerHook
{
    /**
     * Called after database operations (insert/update)
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        string|int $id,
        array $fieldArray,
        DataHandler $dataHandler
    ): void {
        // Only process Document records
        if ($table !== 'tx_sparkdms_domain_model_document') {
            return;
        }

        $logger = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);
        $logger->info('DataHandlerHook triggered', ['status' => $status, 'id' => $id]);

        // Get real UID (handle NEW... placeholders)
        $documentUid = is_numeric($id) ? (int)$id : ($dataHandler->substNEWwithIDs[$id] ?? 0);
        
        if ($documentUid <= 0) {
            $logger->warning('Could not resolve document UID', ['id' => $id]);
            return;
        }

        // Get document data
        $document = $this->getDocument($documentUid);
        if (!$document) {
            $logger->warning('Document not found', ['uid' => $documentUid]);
            return;
        }

        $logger->info('Processing document', ['uid' => $documentUid, 'title' => $document['title']]);

        // Get all versions for this document
        $versions = $this->getDocumentVersions($documentUid);
        
        $logger->info('Found versions', ['count' => count($versions)]);

        // Organize files for each version
        foreach ($versions as $version) {
            $this->organizeVersionFile($version, $document);
        }
    }

    /**
     * Get Document record
     */
    protected function getDocument(int $uid): ?array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_sparkdms_domain_model_document');
        
        $result = $queryBuilder
            ->select('*')
            ->from('tx_sparkdms_domain_model_document')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        return $result ?: null;
    }

    /**
     * Get all DocumentVersion records for a document
     */
    protected function getDocumentVersions(int $documentUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_sparkdms_domain_model_document_version');
        
        return $queryBuilder
            ->select('*')
            ->from('tx_sparkdms_domain_model_document_version')
            ->where(
                $queryBuilder->expr()->eq('document', $queryBuilder->createNamedParameter($documentUid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();
    }

    /**
     * Organize a version's file from _inbox to structured folder
     */
    protected function organizeVersionFile(array $version, array $document): void
    {
        $logger = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);

        try {
            // Get file reference via IRRE relation
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_reference');

            $fileReference = $queryBuilder
                ->select('uid_local')
                ->from('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tx_sparkdms_domain_model_document_version')),
                    $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter('file')),
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($version['uid'], Connection::PARAM_INT))
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            if (!$fileReference || empty($fileReference['uid_local'])) {
                $logger->debug('No file reference for version', ['version_uid' => $version['uid']]);
                return;
            }

            // Get actual file
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            $file = $resourceFactory->getFileObject((int)$fileReference['uid_local']);
            
            if (!$file) {
                $logger->warning('File object not found', ['uid_local' => $fileReference['uid_local']]);
                return;
            }

            $storage = $file->getStorage();
            $currentIdentifier = $file->getIdentifier();

            // Only process files in _inbox
            if (strpos($currentIdentifier, '/_inbox/') !== 0) {
                $logger->debug('File not in _inbox, skipping', ['identifier' => $currentIdentifier]);
                return;
            }

            // Build target path
            $documentDate = new \DateTime('@' . ($document['document_date'] ?: $document['crdate']));
            $year = $documentDate->format('Y');
            $month = $documentDate->format('m');
            
            $typeSlug = $this->getTypeSlug((int)$document['type']) ?: 'uncategorized';
            $titleSlug = $this->slugify($document['title']);
            $docUuid = substr($document['uuid'], 0, 8);
            
            $targetPath = sprintf(
                '/%s/%s/%s/%s-%s/',
                $typeSlug,
                $year,
                $month,
                $titleSlug,
                $docUuid
            );

            $logger->info('Moving file', ['from' => $currentIdentifier, 'to' => $targetPath]);

            // Create target folder recursively
            $targetFolder = $this->createFolderRecursive($storage, $targetPath);

            // Move file
            $file->moveTo($targetFolder);

            // Update sys_file_metadata
            $this->updateFileMetadata($file->getUid(), $document['uid']);

            $logger->info('File organized successfully', ['new_path' => $file->getIdentifier()]);

        } catch (\Exception $e) {
            $logger->error('Failed to organize file', [
                'exception' => $e->getMessage(),
                'version_uid' => $version['uid'],
                'document_uid' => $document['uid'],
            ]);
        }
    }

    /**
     * Get DocumentType slug
     */
    protected function getTypeSlug(int $typeUid): ?string
    {
        if ($typeUid === 0) {
            return null;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_sparkdms_domain_model_document_type');
        
        $result = $queryBuilder
            ->select('slug')
            ->from('tx_sparkdms_domain_model_document_type')
            ->where(
                $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($typeUid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        return $result ?: null;
    }

    /**
     * Create folder structure recursively
     */
    protected function createFolderRecursive(\TYPO3\CMS\Core\Resource\ResourceStorage $storage, string $path): \TYPO3\CMS\Core\Resource\Folder
    {
        $parts = array_filter(explode('/', trim($path, '/')));
        $currentPath = '/';

        foreach ($parts as $part) {
            $folderPath = rtrim($currentPath, '/') . '/' . $part;
            
            if (!$storage->hasFolder($folderPath)) {
                $storage->createFolder($part, $storage->getFolder($currentPath));
            }
            
            $currentPath = $folderPath . '/';
        }

        return $storage->getFolder($currentPath);
    }

    /**
     * Slugify text for folder names
     */
    protected function slugify(string $text): string
    {
        $slugHelper = GeneralUtility::makeInstance(
            \TYPO3\CMS\Core\DataHandling\SlugHelper::class, 
            'tx_sparkdms_domain_model_document',
            'title',
            []
        );
        return $slugHelper->sanitize($text);
    }

    /**
     * Update sys_file_metadata with DMS fields
     */
    protected function updateFileMetadata(int $fileUid, int $documentUid): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_metadata');

        $connection->update(
            'sys_file_metadata',
            [
                'tx_sparkdms_document' => $documentUid,
                'tx_sparkdms_is_protected' => 1,
            ],
            ['file' => $fileUid]
        );
    }
}
