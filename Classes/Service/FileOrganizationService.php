<?php

declare(strict_types=1);

namespace RootBa\SparkDms\Service;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Log\LogManager;
use Psr\Log\LoggerInterface;

/**
 * Service for organizing DMS files into structured folders
 * 
 * Moves files from _inbox/ to structured folders based on:
 * - Document type slug
 * - Year/month from document date
 * - Document title slug + UUID prefix
 * 
 * @author Ernedin Zajko <ezajko@root.ba>
 */
class FileOrganizationService
{
    protected LoggerInterface $logger;

    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
    }

    /**
     * Organize all files for a document (all versions)
     */
    public function organizeDocumentFiles(int $documentUid): void
    {
        $document = $this->getDocument($documentUid);
        if (!$document) {
            $this->logger->warning('Document not found for file organization', ['uid' => $documentUid]);
            return;
        }

        $versions = $this->getDocumentVersions($documentUid);
        foreach ($versions as $version) {
            $this->organizeVersionFile($version, $document);
        }
    }

    /**
     * Organize a single version's file
     */
    public function organizeVersionFile(array $version, array $document): void
    {
        try {
            // Get file reference via IRRE relation
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('sys_file_reference');

            $fileReference = $queryBuilder
                ->select('uid_local')
                ->from('sys_file_reference')
                ->where(
                    $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('tx_sparkdms_domain_model_documentversion')),
                    $queryBuilder->expr()->eq('fieldname', $queryBuilder->createNamedParameter('file')),
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($version['uid'], Connection::PARAM_INT))
                )
                ->setMaxResults(1)
                ->executeQuery()
                ->fetchAssociative();

            if (!$fileReference || empty($fileReference['uid_local'])) {
                $this->logger->debug('No file reference for version', ['version_uid' => $version['uid']]);
                return;
            }

            // Get actual file
            $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
            $file = $resourceFactory->getFileObject((int)$fileReference['uid_local']);
            
            if (!$file) {
                $this->logger->warning('File object not found', ['uid_local' => $fileReference['uid_local']]);
                return;
            }

            $storage = $file->getStorage();
            $currentIdentifier = $file->getIdentifier();

            // Only process files in _inbox
            if (strpos($currentIdentifier, '/_inbox/') !== 0) {
                $this->logger->debug('File not in _inbox, skipping', ['identifier' => $currentIdentifier]);
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

            $this->logger->info('Moving file', ['from' => $currentIdentifier, 'to' => $targetPath]);

            // Create target folder recursively
            $targetFolder = $this->createFolderRecursive($storage, $targetPath);

            // Move file
            $file->moveTo($targetFolder);

            // Update sys_file_metadata
            $this->updateFileMetadata($file->getUid(), $document['uid']);

            $this->logger->info('File organized successfully', ['new_path' => $file->getIdentifier()]);

        } catch (\Exception $e) {
            $this->logger->error('Failed to organize file', [
                'exception' => $e->getMessage(),
                'version_uid' => $version['uid'],
                'document_uid' => $document['uid'],
            ]);
        }
    }

    /**
     * Get Document record
     */
    public function getDocument(int $uid): ?array
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
    public function getDocumentVersions(int $documentUid): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('tx_sparkdms_domain_model_documentversion');
        
        return $queryBuilder
            ->select('*')
            ->from('tx_sparkdms_domain_model_documentversion')
            ->where(
                $queryBuilder->expr()->eq('document', $queryBuilder->createNamedParameter($documentUid, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();
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
            ->getQueryBuilderForTable('tx_sparkdms_domain_model_documenttype');
        
        $result = $queryBuilder
            ->select('slug')
            ->from('tx_sparkdms_domain_model_documenttype')
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
    protected function createFolderRecursive(ResourceStorage $storage, string $path): Folder
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
