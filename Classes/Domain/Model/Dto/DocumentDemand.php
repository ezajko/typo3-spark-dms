<?php

declare(strict_types=1);

namespace RootBa\SparkDms\Domain\Model\Dto;

use RootBa\SparkDms\Domain\Model\DocumentType;
use RootBa\SparkDms\Domain\Model\DocumentCategory;
use DateTime;

class DocumentDemand
{
    protected string $search = '';
    
    protected ?DocumentType $type = null;
    
    protected ?DocumentCategory $category = null;
    
    protected ?DateTime $dateFrom = null;
    
    protected ?DateTime $dateTo = null;
    
    protected int $limit = 0;
    
    protected int $offset = 0;

    protected array $ordering = ['documentDate' => 'DESC'];

    /**
     * Array of allowed category UIDs (from FlexForm).
     * If set, only documents in these categories are returned.
     * @var int[]
     */
    protected array $allowedCategories = [];

    /**
     * Array of allowed type UIDs (from FlexForm).
     * If set, only documents of these types are returned.
     * @var int[]
     */
    protected array $allowedTypes = [];

    public function getSearch(): string
    {
        return $this->search;
    }

    public function setSearch(string $search): void
    {
        $this->search = $search;
    }

    public function getType(): ?DocumentType
    {
        return $this->type;
    }

    public function setType(?DocumentType $type): void
    {
        $this->type = $type;
    }

    public function getCategory(): ?DocumentCategory
    {
        return $this->category;
    }

    public function setCategory(?DocumentCategory $category): void
    {
        $this->category = $category;
    }

    public function getDateFrom(): ?DateTime
    {
        return $this->dateFrom;
    }

    public function setDateFrom(?DateTime $dateFrom): void
    {
        $this->dateFrom = $dateFrom;
    }

    public function getDateTo(): ?DateTime
    {
        return $this->dateTo;
    }

    public function setDateTo(?DateTime $dateTo): void
    {
        $this->dateTo = $dateTo;
    }

    public function getLimit(): int
    {
        return $this->limit;
    }

    public function setLimit(int $limit): void
    {
        $this->limit = $limit;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function setOffset(int $offset): void
    {
        $this->offset = $offset;
    }

    public function getOrdering(): array
    {
        return $this->ordering;
    }

    public function setOrdering(array $ordering): void
    {
        $this->ordering = $ordering;
    }

    /**
     * Get allowed category UIDs (from FlexForm)
     * @return int[]
     */
    public function getAllowedCategories(): array
    {
        return $this->allowedCategories;
    }

    /**
     * Set allowed category UIDs (from FlexForm)
     * @param int[] $allowedCategories
     */
    public function setAllowedCategories(array $allowedCategories): void
    {
        $this->allowedCategories = $allowedCategories;
    }

    /**
     * Get allowed type UIDs (from FlexForm)
     * @return int[]
     */
    public function getAllowedTypes(): array
    {
        return $this->allowedTypes;
    }

    /**
     * Set allowed type UIDs (from FlexForm)
     * @param int[] $allowedTypes
     */
    public function setAllowedTypes(array $allowedTypes): void
    {
        $this->allowedTypes = $allowedTypes;
    }
}
