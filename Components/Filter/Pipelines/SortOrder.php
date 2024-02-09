<?php
namespace App\Http\Controllers\Front\Components\Titamik\Filter\Piplines;

use Illuminate\Database\Eloquent\Builder;

class SortOrder
{
    private $value;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $builder//->orderBy($this->value, $this->direction);
        ->orderByRaw("{$this->value}");

        return $next($builder);
    }
}