<?php

use Spatie\QueryBuilder\Exceptions\InvalidAppendQuery;
use Spatie\QueryBuilder\Tests\TestClasses\Models\AppendModel;

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
        ->allowedAppends(['fullname', 'reversename'])
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
        ->allowedAppends(['fullname', 'reversename'])
        ->first();

    expect($model->toArray())
        ->toHaveKey('fullname')
        ->not->toHaveKey('reversename');
});
