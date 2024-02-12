<?php namespace App\Http\Controllers\Front\Components\Titamik\Filter\Piplines;

use  Illuminate\Database\Eloquent\Builder;

final class EquipmendFieldLikeInFilter
{
    public function __construct(
        private int $column,
        private array $value
    )
    {}

    public function handle(Builder $builder, \Closure $next)
    {
        $query = $this->generateQuery();
        $builder->whereRaw("(select count(equipment_property_rel.equipment_id) from equipment_property_rel
            where equipment_property_rel.property_id = {$this->column} and ({$query})
                and equipment_property_rel.equipment_id = equipment.id) > 0");

        return $next($builder);
    }

    private function generateQuery(): string
    {
        $query = [];

        foreach ($this->value as $value) {
            $query[] = "equipment_property_rel.value_string like '%$value%'";
        }

        return implode(' OR ', $query);
    }
}