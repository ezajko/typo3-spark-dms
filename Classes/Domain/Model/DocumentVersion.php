<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;
use TYPO3\CMS\Extbase\Domain\Model\FileReference;

class DocumentVersion extends AbstractEntity
{
    protected string $versionLabel = '';
    protected string $uuid = '';
    protected ?FileReference $file = null;
    protected ?Document $document = null;

    public function getVersionLabel(): string
    {
        return $this->versionLabel;
    }

    public function setVersionLabel(string $versionLabel): void
    {
        $this->versionLabel = $versionLabel;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getFile(): ?FileReference
    {
        return $this->file;
    }

    public function setFile(?FileReference $file): void
    {
        $this->file = $file;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): void
    {
        $this->document = $document;
    }
}
