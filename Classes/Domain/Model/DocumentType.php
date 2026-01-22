<?php

declare(strict_types=1);

namespace RootBa\SparkDms\Domain\Model;

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class DocumentType extends AbstractEntity
{
    protected string $title = '';
    protected string $slug = '';
    protected string $uuid = '';
    protected string $description = '';
    protected ?DocumentType $parent = null;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getParent(): ?DocumentType
    {
        return $this->parent;
    }

    public function setParent(?DocumentType $parent): void
    {
        $this->parent = $parent;
    }
}
