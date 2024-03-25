<?php namespace App\Http\Controllers\Front\Components\Titamik\Filter\Piplines;

use  Illuminate\Database\Eloquent\Builder;

final class ColumnInFilter
{
    public function __construct(
        private string $column,
        private array $value,
        private string $operator = 'and'
    ){
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $builder->whereIn(
            $this->column,
            $this->value,
            $this->operator
        );

        return $next($builder);
    }
}