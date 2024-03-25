<?php
namespace App\Http\Controllers\Front\Components\Titamik\Filter\Piplines;

use Illuminate\Database\Eloquent\Builder;

class SortOrder
{
    public function __construct(
        private readonly string $value
    )
    {
    }

    public function handle(Builder $builder, \Closure $next)
    {
        [$column, $direction] = explode(',', $this->value, 2);
        $builder->orderBy($column, $direction);

        return $next($builder);
    }
}