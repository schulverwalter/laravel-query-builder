<?php

use Illuminate\Http\Request;
use Spatie\QueryBuilder\Exceptions\InvalidAppendQuery;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\Tests\TestClasses\Models\AppendModel;
use Spatie\QueryBuilder\Tests\TestClasses\Models\TestModel;

beforeEach(function () {
    $this->models = AppendModel::factory()->count(5)->create();
});

it('does not require appends', function () {
    $models = createQueryFromAppendRequest('')
        ->allowedAppends('fullname')
        ->get();

    expect($models)->toHaveCount(5);
});

it('can append attributes to a single model', function () {
    $model = createQueryFromAppendRequest('fullname')
        ->allowedAppends('fullname')
        ->first();

    expect($model->toArray())->toHaveKey('fullname');
});

it('can append attributes to a collection of models', function () {
    $models = createQueryFromAppendRequest('fullname')
        ->allowedAppends('fullname')
        ->get();

    $models->each(function ($model) {
        expect($model->toArray())->toHaveKey('fullname');
    });
});

it('can append multiple attributes', function () {
    $models = createQueryFromAppendRequest('fullname,reversename')
        ->allowedAppends('fullname', 'reversename')
        ->get();

    $models->each(function ($model) {
        expect($model->toArray())
            ->toHaveKey('fullname')
            ->toHaveKey('reversename');
    });
});

it('can append attributes to paginated results', function () {
    $models = createQueryFromAppendRequest('fullname')
        ->allowedAppends('fullname')
        ->paginate(2);

    collect($models->items())->each(function ($model) {
        expect($model->toArray())->toHaveKey('fullname');
    });
});

it('guards against invalid appends', function () {
    $this->expectException(InvalidAppendQuery::class);

    createQueryFromAppendRequest('random-attribute-to-append')
        ->allowedAppends('fullname');
});

it('can allow appends by passing multiple arguments', function () {
    $models = createQueryFromAppendRequest('fullname,reversename')
        ->allowedAppends('fullname', 'reversename')
        ->get();

    $models->each(function ($model) {
        expect($model->toArray())
            ->toHaveKey('fullname')
            ->toHaveKey('reversename');
    });
});

it('does not append attributes that were not requested', function () {
    $model = createQueryFromAppendRequest('fullname')
        ->allowedAppends('fullname', 'reversename')
        ->first();

    expect($model->toArray())
        ->toHaveKey('fullname')
        ->not->toHaveKey('reversename');
});

/**
 * Test models with a related model each, which has a nested related model, queried with the
 * given includes and appends.
 */
function createQueryFromIncludeAndAppendRequest(string $includes, string $appends): QueryBuilder
{
    TestModel::factory()->count(2)->create()->each(function (TestModel $model) {
        $model
            ->relatedModels()->create(['name' => 'Related'])
            ->nestedRelatedModels()->create(['name' => 'Nested']);
    });

    return QueryBuilder::for(TestModel::class, new Request([
        'include' => $includes,
        'append' => $appends,
    ]));
}

it('can append attributes to the models of an included relation in dot notation', function () {
    $models = createQueryFromIncludeAndAppendRequest('relatedModels', 'relatedModels.reversed_name')
        ->allowedIncludes('relatedModels')
        ->allowedAppends('relatedModels.reversed_name')
        ->get();

    $models->each(function (TestModel $model) {
        expect($model->toArray())->not->toHaveKey('reversed_name');
        expect($model->relatedModels->first()->toArray())->toHaveKey('reversed_name', 'detaleR');
    });
});

it('can append attributes to the models of a nested included relation', function () {
    $models = createQueryFromIncludeAndAppendRequest(
        'relatedModels.nestedRelatedModels',
        'relatedModels.nestedRelatedModels.reversed_name',
    )
        ->allowedIncludes('relatedModels.nestedRelatedModels')
        ->allowedAppends('relatedModels.nestedRelatedModels.reversed_name')
        ->get();

    $models->each(function (TestModel $model) {
        expect($model->relatedModels->first()->toArray())->not->toHaveKey('reversed_name');
        expect($model->relatedModels->first()->nestedRelatedModels->first()->toArray())
            ->toHaveKey('reversed_name', 'detseN');
    });
});

it('does not load a relation to append to it', function () {
    $models = createQueryFromIncludeAndAppendRequest('', 'relatedModels.reversed_name')
        ->allowedAppends('relatedModels.reversed_name')
        ->get();

    $models->each(function (TestModel $model) {
        expect($model->relationLoaded('relatedModels'))->toBeFalse();
    });
});

it('guards against appends to a relation that are not allowed', function () {
    $this->expectException(InvalidAppendQuery::class);

    createQueryFromIncludeAndAppendRequest('relatedModels', 'relatedModels.reversed_name')
        ->allowedIncludes('relatedModels')
        ->allowedAppends('reversed_name');
});
