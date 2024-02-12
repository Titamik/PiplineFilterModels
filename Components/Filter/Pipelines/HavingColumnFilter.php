<?php namespace App\Http\Controllers\Front\Components\Titamik\Filter\Piplines;

use  Illuminate\Database\Eloquent\Builder;

final class HavingColumnFilter
{
    public function __construct(
        private string $column,
        private string $value,
        private string $operator = '=',
    )
    {
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $builder->having(
            $this->column,
            $this->operator,
            $this->value
        );

        return $next($builder);
    }
}