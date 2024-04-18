<?php

namespace Nvmcommunity\EloquentApi;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Nvmcommunity\Alchemist\RestfulApi\AlchemistRestfulApi;
use Nvmcommunity\Alchemist\RestfulApi\Common\Exceptions\AlchemistRestfulApiException;
use Nvmcommunity\Alchemist\RestfulApi\Common\Integrations\AlchemistQueryable;
use Nvmcommunity\Alchemist\RestfulApi\Common\Notification\ErrorBag;
use Nvmcommunity\Alchemist\RestfulApi\FieldSelector\Handlers\FieldSelector;
use Nvmcommunity\Alchemist\RestfulApi\ResourceFilter\Handlers\ResourceFilter;
use Nvmcommunity\Alchemist\RestfulApi\ResourcePaginations\OffsetPaginator\Handlers\ResourceOffsetPaginator;
use Nvmcommunity\Alchemist\RestfulApi\ResourceSearch\Handlers\ResourceSearch;
use Nvmcommunity\Alchemist\RestfulApi\ResourceSort\Handlers\ResourceSort;

class EloquentBuilder
{
    /**
     * @param AlchemistRestfulApi $alchemistRestfulApi
     * @param Builder $subject
     */
    public function __construct(protected AlchemistRestfulApi $alchemistRestfulApi, protected Builder $subject)
    {
        if ($alchemistRestfulApi->isComponentUses(FieldSelector::class)) {
            $this->handleFieldSelector();
        }

        if ($alchemistRestfulApi->isComponentUses(ResourceFilter::class)) {
            $this->handleResourceFilter();
        }

        if ($alchemistRestfulApi->isComponentUses(ResourceOffsetPaginator::class)) {
            $this->handleOffsetPaginator();
        }

        if ($alchemistRestfulApi->isComponentUses(ResourceSort::class)) {
            $this->handleResourceSort();
        }

        if ($alchemistRestfulApi->isComponentUses(ResourceSearch::class)) {
            $this->handleResourceSearch();
        }
    }

    /**
     * @param Builder|Model|string $subject
     * @param AlchemistQueryable|string $apiClass
     * @param array $input
     *
     * @return EloquentBuilder
     * @throws AlchemistRestfulApiException
     */
    public static function for(Builder|Model|string $subject, AlchemistQueryable|string $apiClass, array $input): EloquentBuilder
    {
        if (is_subclass_of($subject, Model::class)) {
            $subject = $subject::query();
        }

        $alchemistRestfulApi = AlchemistRestfulApi::for($apiClass, $input);

        return new static($alchemistRestfulApi, $subject);
    }

    /**
     * @param ErrorBag|null $errorBag
     * @return ErrorBag
     */
    public function validate(?ErrorBag &$errorBag = null): ErrorBag
    {
        return $this->alchemistRestfulApi->validate($errorBag);
    }

    /**
     * @return AlchemistRestfulApi
     */
    public function getAlchemistRestfulApi(): AlchemistRestfulApi
    {
        return $this->alchemistRestfulApi;
    }

    /**
     * @return Builder
     */
    public function getBuilder(): Builder
    {
        return $this->subject;
    }

    /**
     * @return void
     */
    protected function handleFieldSelector(): void
    {
        $rootNamespace = '$';

        $fields = $this->alchemistRestfulApi->fieldSelector()->fields($rootNamespace);

        foreach ($fields as $field) {
            $fieldStructure = $this->alchemistRestfulApi->fieldSelector()->getFieldStructure("$rootNamespace.{$field->getName()}");

            if (! $fieldStructure) {
                continue;
            }

            if ($fieldStructure['type'] === 'atomic') {
                $this->subject->addSelect($field->getName());
            }
        }
    }

    /**
     * @return void
     */
    protected function handleResourceFilter(): void
    {
        foreach ($this->alchemistRestfulApi->resourceFilter()->filtering() as $filteringObj) {
            match ($filteringObj->getOperator()) {
                'in' => $this->subject->whereIn($filteringObj->getFiltering(), $filteringObj->getFilteringValue()),
                'not_in' => $this->subject->whereNotIn($filteringObj->getFiltering(), $filteringObj->getFilteringValue()),
                'between' => $this->subject->whereBetween($filteringObj->getFiltering(), $filteringObj->getFilteringValue()),
                'not_between' => $this->subject->whereNotBetween($filteringObj->getFiltering(), $filteringObj->getFilteringValue()),
                'contains' => $this->subject->where(
                    $filteringObj->getFiltering(), 'like', "%{$filteringObj->getFilteringValue()}%"
                ),
                default => $this->subject->where(
                    $filteringObj->getFiltering(), $filteringObj->getOperator(), $filteringObj->getFilteringValue()
                ),
            };
        }
    }

    /**
     * @return void
     */
    protected function handleOffsetPaginator(): void
    {
        $offsetPaginate = $this->alchemistRestfulApi->resourceOffsetPaginator()->offsetPaginate();

        if (! empty($offsetPaginate->getLimit())) {
            $this->subject->limit($offsetPaginate->getLimit());
        }

        if (! empty($offsetPaginate->getOffset())) {
            $this->subject->offset($offsetPaginate->getOffset());
        }
    }

    /**
     * @return void
     */
    protected function handleResourceSort(): void
    {
        $sort = $this->alchemistRestfulApi->resourceSort()->sort();

        if (! empty($sort->getSortField())) {
            $this->subject->orderBy($sort->getSortField(), $sort->getDirection());
        }
    }

    /**
     * @return void
     */
    protected function handleResourceSearch(): void
    {
        $search = $this->alchemistRestfulApi->resourceSearch()->search();

        if (! empty($search->getSearchCondition())) {
            $this->subject->where($search->getSearchCondition(), 'like', "%{$search->getSearchValue()}%");
        }
    }
}