<?php

declare(strict_types=1);

namespace RootBa\SparkDms\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Annotation\ORM\Cascade;
use DateTime;


class Document extends AbstractEntity
{
    protected string $title = '';
    protected string $registryNumber = '';
    protected string $uuid = '';
    protected bool $isProtected = false;
    protected string $description = '';
    protected ?DateTime $documentDate = null;
    
    protected ?DocumentType $type = null;

    /**
     * @var ObjectStorage<DocumentCategory>
     */
    protected ObjectStorage $category;

    /**
     * @var ObjectStorage<DocumentVersion>
     * @Cascade("remove")
     */
    protected ObjectStorage $versions;

    /**
     * @var ObjectStorage<Document>
     */
    protected ObjectStorage $relatedDocuments;

    public function __construct()
    {
        $this->category = new ObjectStorage();
        $this->versions = new ObjectStorage();
        $this->relatedDocuments = new ObjectStorage();
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getRegistryNumber(): string
    {
        return $this->registryNumber;
    }

    public function setRegistryNumber(string $registryNumber): void
    {
        $this->registryNumber = $registryNumber;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getIsProtected(): bool
    {
        return $this->isProtected;
    }

    public function setIsProtected(bool $isProtected): void
    {
        $this->isProtected = $isProtected;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getDocumentDate(): ?DateTime
    {
        return $this->documentDate;
    }

    public function setDocumentDate(?DateTime $documentDate): void
    {
        $this->documentDate = $documentDate;
    }

    public function getType(): ?DocumentType
    {
        return $this->type;
    }

    public function setType(?DocumentType $type): void
    {
        $this->type = $type;
    }

    /**
     * @return ObjectStorage<DocumentCategory>
     */
    public function getCategory(): ObjectStorage
    {
        return $this->category;
    }

    public function setCategory(ObjectStorage $category): void
    {
        $this->category = $category;
    }

    public function addCategory(DocumentCategory $category): void
    {
        $this->category->attach($category);
    }

    public function removeCategory(DocumentCategory $category): void
    {
        $this->category->detach($category);
    }

    /**
     * @return ObjectStorage<DocumentVersion>
     */
    public function getVersions(): ObjectStorage
    {
        return $this->versions;
    }

    public function setVersions(ObjectStorage $versions): void
    {
        $this->versions = $versions;
    }

    public function addVersion(DocumentVersion $version): void
    {
        $this->versions->attach($version);
    }

    public function removeVersion(DocumentVersion $version): void
    {
        $this->versions->detach($version);
    }

    /**
     * Helper: Get the Latest Version (sorted by tstamp descending)
     */
    public function getLatestVersion(): ?DocumentVersion
    {
        if ($this->versions->count() === 0) {
            return null;
        }

        $sorted = $this->getSortedVersions();
        return $sorted[0] ?? null;
    }

    /**
     * Helper: Get all versions sorted by createdAt descending (newest first)
     * 
     * @return DocumentVersion[]
     */
    public function getSortedVersions(): array
    {
        $versionsArray = $this->versions->toArray();
        usort($versionsArray, function (DocumentVersion $a, DocumentVersion $b) {
            // Sort by createdAt descending (newest first), fallback to UID
            $createdDiff = $b->getCreatedAt() <=> $a->getCreatedAt();
            return $createdDiff !== 0 ? $createdDiff : ($b->getUid() <=> $a->getUid());
        });
        return $versionsArray;
    }

    /**
     * Helper: Get the File from Latest Version
     */
    public function getFile(): ?FileReference
    {
        $bestVersion = $this->getLatestVersion();
        return $bestVersion ? $bestVersion->getFile() : null;
    }

    /**
     * @return ObjectStorage<Document>
     */
    public function getRelatedDocuments(): ObjectStorage
    {
        return $this->relatedDocuments;
    }

    public function setRelatedDocuments(ObjectStorage $relatedDocuments): void
    {
        $this->relatedDocuments = $relatedDocuments;
    }

    public function addRelatedDocument(Document $document): void
    {
        $this->relatedDocuments->attach($document);
    }

    public function removeRelatedDocument(Document $document): void
    {
        $this->relatedDocuments->detach($document);
    }

    /**
     * Get all related documents (bidirectional)
     * Returns documents where THIS document is in their related_documents OR
     * documents that are in this document's related_documents.
     * 
     * @return Document[]
     */
    public function getAllRelatedDocuments(): array
    {
        $related = [];
        $seenUids = [];

        // Direct relations (this document has these as related)
        foreach ($this->relatedDocuments as $doc) {
            if (!in_array($doc->getUid(), $seenUids, true)) {
                $related[] = $doc;
                $seenUids[] = $doc->getUid();
            }
        }

        // Reverse relations (these documents have this as their related)
        // Query MM table for uid_foreign = this.uid
        if ($this->getUid() > 0) {
            $queryBuilder = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                \TYPO3\CMS\Core\Database\ConnectionPool::class
            )->getQueryBuilderForTable('tx_sparkdms_document_related_mm');

            $reverseUids = $queryBuilder
                ->select('uid_local')
                ->from('tx_sparkdms_document_related_mm')
                ->where(
                    $queryBuilder->expr()->eq('uid_foreign', $queryBuilder->createNamedParameter($this->getUid(), \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchAllAssociative();

            if (!empty($reverseUids)) {
                $docRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
                    \RootBa\SparkDms\Domain\Repository\DocumentRepository::class
                );
                
                foreach ($reverseUids as $row) {
                    $uid = (int)$row['uid_local'];
                    if (!in_array($uid, $seenUids, true)) {
                        $doc = $docRepository->findByUid($uid);
                        if ($doc !== null) {
                            $related[] = $doc;
                            $seenUids[] = $uid;
                        }
                    }
                }
            }
        }

        return $related;
    }
}
