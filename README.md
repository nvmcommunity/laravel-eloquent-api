# Laravel Eloquent API

## Description

Easily build Eloquent queries from API requests using Alchemist Restful API.

## Basic usage

### Step 1: Define the API class

```php
class UserApiQuery extends AlchemistQueryable
{
    /**
     * @param FieldSelector $fieldSelector
     * @return void
     */
    public static function fieldSelector(FieldSelector $fieldSelector): void
    {
        $fieldSelector->defineFieldStructure([
            'id', 'name', 'email'
        ])
        ->defineDefaultFields(['id']);
    }

    /**
     * @param ResourceFilter $resourceFilter
     * @return void
     */
    public static function resourceFilter(ResourceFilter $resourceFilter): void
    {
        $resourceFilter->defineFilteringRules([
            FilteringRules::String('id', ['eq']),
            FilteringRules::String('name', ['eq', 'contains']),
            FilteringRules::String('email', ['eq', 'contains']),
        ]);
    }

    /**
     * @param ResourceOffsetPaginator $resourceOffsetPaginator
     * @return void
     */
    public static function resourceOffsetPaginator(ResourceOffsetPaginator $resourceOffsetPaginator): void
    {
        $resourceOffsetPaginator->defineDefaultLimit(10)
            ->defineMaxLimit(1000);
    }

    /**
     * @param ResourceSearch $resourceSearch
     * @return void
     */
    public static function resourceSearch(ResourceSearch $resourceSearch): void
    {
        $resourceSearch->defineSearchCondition('name');
    }

    /**
     * @param ResourceSort $resourceSort
     * @return void
     * @throws AlchemistRestfulApiException
     */
    public static function resourceSort(ResourceSort $resourceSort): void
    {
        $resourceSort->defineDefaultSort('id')
            ->defineDefaultDirection('desc')
            ->defineSortableFields(['id', 'name']);
    }
}
```
### Step 2: Validate the input parameters

Make sure to validate the input parameters passed in from the request input by using the `validate` method.

```php

// Assuming that the input parameters are passed in from the request input
$input = [
    'fields' => 'name,email',
    'filtering' => [
        'name:contains' => "John"
    ],
];

$eloquentBuilder = EloquentBuilder::for(User::class, UserApiQuery::class, $input);

if (! $eloquentBuilder->validate($e)->passes()) {
    var_dump(json_encode($e->getErrors())); die();
}
```

### Step 3: Get the result

```php
var_dump($eloquentBuilder->getBuilder()->toSql());
```