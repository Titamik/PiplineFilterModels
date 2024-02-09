<?php namespace App\Http\Controllers\Front\Components\Titamik\Filter\Piplines;

use  Illuminate\Database\Eloquent\Builder;

class EquipmendFieldLikeInFilter
{
    private $id;
    private $values;

    public function __construct(int $id, array $values)
    {
        $this->id = $id;
        $this->values = $values;
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $query = [];

        foreach ($this->values as $value) {
            $query[] = "equipment_property_rel.value_string like '%$value%'";
        }
        
        $query = implode(' OR ', $query);

        $builder->whereRaw("(select count(equipment_property_rel.equipment_id) from equipment_property_rel
            where equipment_property_rel.property_id = {$this->id} and ({$query})
                and equipment_property_rel.equipment_id = equipment.id) > 0");

        return $next($builder);
    }
}