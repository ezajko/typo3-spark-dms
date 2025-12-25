<?php

declare(strict_types=1);

namespace EtfUnsa\SparkDms\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class DocumentCategory extends AbstractEntity
{
    protected string $title = '';
    protected string $slug = '';
    protected string $uuid = '';
    protected ?DocumentCategory $parent = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getUuid(): string
    {
        return $this->uuid;
    }

    public function setUuid(string $uuid): void
    {
        $this->uuid = $uuid;
    }

    public function getParent(): ?DocumentCategory
    {
        return $this->parent;
    }

    public function setParent(?DocumentCategory $parent): void
    {
        $this->parent = $parent;
    }
}
