<?php namespace App\Http\Controllers\Front\Components\Titamik\Filter\Piplines;

use  Illuminate\Database\Eloquent\Builder;

class HavingColumnFilter
{
    private $column;
    private $operator = '=';
    private $value;

    public function __construct(string $column, string $operator, string $value)
    {
        $this->column = $column;
        $this->value = $value;
        $this->operator = $operator;
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $builder->having($this->column, $this->operator, $this->value);
        return $next($builder);
    }
}