<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\Exceptions\InvalidAppendQuery;

trait AppendsAttributesToResults
{
    protected Collection $allowedAppends;

    public function allowedAppends(string ...$appends): static
    {
        $this->allowedAppends = collect($appends);

        $this->ensureAllAppendsExist();

        return $this;
    }

    /**
     * Appends in dot notation reach the models of a loaded relation, like includes:
     * `?include=posts&append=posts.excerpt` appends `excerpt` to every loaded post.
     *
     * @param Collection<int, Model> $results
     * @return Collection<int, Model>
     */
    protected function addAppendsToResults(Collection $results): Collection
    {
        $this->request->appends()->each(function (string $append) use ($results) {
            $this->modelsToAppendTo($results, $append)->each->append(Str::afterLast($append, '.'));
        });

        return $results;
    }

    /**
     * The models an append belongs to: the results themselves, or for `posts.excerpt` the
     * models of their `posts` relation. A relation that was not loaded is not loaded for it.
     *
     * @param Collection<int, Model> $results
     * @return Collection<int, Model>
     */
    protected function modelsToAppendTo(Collection $results, string $append): Collection
    {
        if (! str_contains($append, '.')) {
            return $results;
        }

        return collect(explode('.', Str::beforeLast($append, '.')))
            ->reduce(fn (Collection $models, string $relation) => $models
                ->map(fn (Model $model) => $model->relationLoaded($relation) ? $model->getRelation($relation) : null)
                ->flatten()
                ->filter(), $results);
    }

    protected function ensureAllAppendsExist(): void
    {
        $appends = $this->request->appends();

        $diff = $appends->diff($this->allowedAppends);

        if ($diff->count()) {
            throw InvalidAppendQuery::appendsNotAllowed($diff, $this->allowedAppends);
        }
    }
}
