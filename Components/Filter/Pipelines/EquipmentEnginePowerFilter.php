<?php
namespace App\Http\Controllers\Front\Components\Titamik\Filter\Piplines;

use Illuminate\Database\Eloquent\Builder;

class EquipmentEnginePowerFilter
{
    private $id;
    private $operator = '=';
    private $value;

    public function __construct(int $id, string $operator, string $value)
    {
        $this->id = $id;
        $this->operator = $operator;
        $this->value = $value;
    }

    public function handle(Builder $builder, \Closure $next)
    {
        $builder->whereRaw("(select count(equipment_property_rel.equipment_id) from equipment_property_rel
            where equipment_property_rel.property_id = {$this->id} 
            and substring(equipment_property_rel.value_string, 1, instr(equipment_property_rel.value_string, ' ')-1) {$this->operator} {$this->value}
            and equipment_property_rel.equipment_id = equipment.id) > 0");

        return $next($builder);
    }
}