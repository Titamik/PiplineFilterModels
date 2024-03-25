<?php
namespace App\Http\Controllers\Front\Components\Titamik\Filter\Piplines;

use Illuminate\Database\Eloquent\Builder;

final class EquipmendFieldWhereFilter
{
    public function __construct(
        private int $column,
        private string $value,
        private string $operator = '=',
    )
    {
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $builder->whereRaw("(select count(equipment_property_rel.equipment_id) from equipment_property_rel
            where equipment_property_rel.property_id = {$this->column} and 
                  equipment_property_rel.value_string {$this->operator} {$this->value}
                and equipment_property_rel.equipment_id = equipment.id) > 0");

        return $next($builder);
    }
}