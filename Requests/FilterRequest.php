<?php

namespace App\Http\Request;

use Illuminate\Foundation\Http\FormRequest;

class FilterRequest extends FormRequest
{

    public function validationData()
    {
        return array_merge(
            ['sort_order' => 'model.sort,asc'], // Стандартное значение в запросе
            $this->all()
        );
    }

    public function rules(): array
    {
        $maxYear = now()->year;
        return [
            'brand_id' => 'array',
            'brand_id.*' => 'integer',
            'model_id' => 'array',
            'model_id.*' => 'integer',
            'body_type' => 'array',
            'body_type.*' => 'integer',
            'engine_type' => 'array',
            'engine_type.*' => 'integer',
            'type_drive' => 'array',
            'type_drive.*' => 'integer',
            'min_year' => "integer|min:1970|max:$maxYear",
            'max_year' => "integer|min:1970|max:$maxYear",
            'sort_order' => 'string',
            // Далее не совсем ясно какие значения прилетают с фронта, точнее в каком формате, условно сделал их строками
            'min_mileage' => 'string',
            'max_mileage' => 'string',
            'min_price' => 'string',
            'max_price' => 'string',
            'kpp' => 'string',
            'min_engine_volume' => 'string',
            'max_engine_volume' => 'string',
            'min_engine_power' => 'string',
            'max_engine_power' => 'string',
        ];
    }
}