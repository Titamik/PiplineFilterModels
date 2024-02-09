<?php namespace App\Http\Controllers\Front\Components\Titamik\Filter\Piplines;

use  Illuminate\Database\Eloquent\Builder;

class ColumnInFilter
{
    private $column;
    private $values;
    private $boolean = 'and';

    public function __construct(string $column, array $values, string $boolean = 'and')
    {
        $this->column = $column;
        $this->values = $values;
        $this->boolean = $boolean;
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $builder->whereIn($this->column, $this->values, $this->boolean);
        return $next($builder);
    }
}