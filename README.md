# Laravel Eloquent API

Easily build Eloquent queries from API requests using Alchemist Restful API.

## Description

This is a package that helps you integrate Alchemist Restful API with Laravel Eloquent. for more information about concepts and usage of Alchemist Restful API, please refer to the [Alchemist Restful API documentation](https://github.com/nvmcommunity/alchemist-restful-api)

## Installation

```bash
composer require nvmcommunity/laravel-eloquent-api
```

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
// In Laravel, you can get the input parameters by calling the `input` method on the request object or using the `request` helper function
$input = [
    'fields' => 'name,email',
    'filtering' => [
        'name:contains' => "John"
    ],
];
// Apply aspects defined in the UserApiQuery class to build the query for the User model from the input parameters
$eloquentBuilder = EloquentBuilder::for(User::class, UserApiQuery::class, $input);

// Validate the input parameters
if (! $eloquentBuilder->validate($e)->passes()) {
    var_dump(json_encode($e->getErrors())); die();
}
```

### Step 3: Done! Get the Eloquent query builder and execute the query

After validating the input parameters, you can get the Eloquent query builder by calling the `getBuilder` method. This method will return an instance of `Illuminate\Database\Eloquent\Builder` that already have the query constraints applied based on the input parameters.

```php
var_dump($eloquentBuilder->getBuilder()->toSql());
```

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).