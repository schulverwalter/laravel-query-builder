<?php

namespace Spatie\QueryBuilder\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\Exceptions\InvalidAppendQuery;

trait AppendsAttributesToResults
{
    protected Collection $allowedAppends;

    public function allowedAppends(string|array ...$appends): static
    {
        $this->allowedAppends = collect($appends)->flatten();

        $this->ensureAllAppendsExist();

        return $this;
    }

    /**
     * @param Collection<int, Model> $results
     * @return Collection<int, Model>
     */
    protected function addAppendsToResults(Collection $results): Collection
    {
        return $results->each(function (Model $result) {
            return $result->append($this->request->appends()->toArray());
        });
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
