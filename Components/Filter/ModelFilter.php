<?php
namespace App\Http\Controllers\Components\Filter;

use App\Http\Controllers\Front\Components\Titamik\Filter\Piplines\ColumnInFilter;
use App\Http\Controllers\Front\Components\Titamik\Filter\Piplines\EquipmendFieldLikeInFilter;
use App\Http\Controllers\Front\Components\Titamik\Filter\Piplines\EquipmendFieldWhereFilter;
use App\Http\Controllers\Front\Components\Titamik\Filter\Piplines\HavingColumnFilter;
use App\Http\Controllers\Front\Components\Titamik\Filter\Piplines\SortOrder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Pipeline\Pipeline;
use Samovar\VC\Models\Equipment;


class ModelFilter
{
    private int $projectId;

    public function __construct(
        private int $isNew = 1,
        private bool $isActive = true,
        private int|null $isArchived = null
    )
    {
        $this->projectId = (int) env('PROJECT_ID');
    }

    /**
     * Забираем все нужные модели для изначальной работы
     * Обычно, это активная неархивная модель нового авто
     */
    protected function getBuilder()
    {
        // Учитывая логику метода, нет смысла запоминать промежуточный результат в свойстве объекта
        return static::generateBuilder($this->projectId)
                    ->where('is_new', $this->isNew)
                    ->where('project_model_rel.status', $this->isActive) // Сама таблица из GlobalScope
                    ->when($this->isArchived, static function(Builder $query, int $isArchived): void {
                        $query->where('project_model_rel.type', $isArchived);
                    });
    }

    public function pipe(array $pipelines = [], Builder|null $builder = null): Builder
    {
        return app(Pipeline::class)
            ->send($builder ?? $this->getBuilder())
            ->through($pipelines)
            ->thenReturn();
    }

    public function getPipelines(array $attributes): array
    {
        $mapped = static::pipeMapped();
        $pipelines = [];
        foreach($attributes as $key => $value) {
            if (isset($mapped[$key])) {
                if (count($mapped[$key]) === 1) {
                    $pipelineClass = $mapped[$key][0];
                    $pipelineOptions = [];
                } else {
                    [$pipelineClass, $pipelineOptions] = $mapped[$key];
                }

                $pipelines[] = new $pipelineClass(
                    ...array_merge($pipelineOptions, ['value' => $value])
                );
            }
        }

        return $pipelines;
    }

    public function getByModelsIds(array $modelIds, string|null $sort = null): Builder
    {
        return $this->pipe([
                new ColumnInFilter('model.id', $modelIds),
                ...($sort ? new SortOrder($sort) : [])
            ],
            static::generateBuilder($this->projectId)
        );
    }

    private static function generateBuilder(string $projectId): Builder
    {
        return Equipment::select([
                'model.id',
                'model.title',
                'model.slug',
                'model.body_type',
                'model.year',
                'model.mileage',
                'brand.id as brand_id',
                'brand.title as brand_title',
                'brand.slug as brand_slug',
                'project_model_rel.type',
                'power',
                'type_drive',
                'kpp',
                DB::raw('(select min(rec_price) from equipment where equipment.status = 1 and equipment.model_id = model.id and equipment.rec_price > 0) as rec_price'),
                DB::raw("(select min(price) from price where price.equipment_id in (select equipment.id from equipment
                where equipment.status = 1 and equipment.model_id = model.id) and price.project_id = {$projectId} and price.price > 0) as min_price"),
                DB::raw('(select CONCAT_WS(",", media.id, media.object_type, media.type, media.path, media.title, media.alt)
                from media where media.object_type = 2 and media.type = 2 and media.item_id = model.id and media.status = 1 limit 1) as media')
            ])
            ->leftJoin('model', 'model.id', 'equipment.model_id')
            ->leftJoin('project_model_rel', 'project_model_rel.model_id', 'model.id')
            ->leftJoin('brand', 'brand.id', 'model.brand_id');
    }

    // По хорошему бы спрятать в конфиг, но допускаю и наличие в классе фильтрации
    private static function pipeMapped(): array
    {
        return [
            'brand_id' => [
                ColumnInFilter::class
            ],
            'model_id' => [
                ColumnInFilter::class
            ],
            'body_type' => [
                ColumnInFilter::class
            ],
            'min_year' => [
                HavingColumnFilter::class,
                [
                    'column' => 'year',
                    'operator' => '>=',
                ]
            ],
            'max_year' => [
                HavingColumnFilter::class,
                [
                    'column' => 'year',
                    'operator' => '<=',
                ]
            ],
            'min_mileage' => [
                HavingColumnFilter::class,
                [
                    'column' => 'mileage',
                    'operator' => '>=',
                ]
            ],
            'max_mileage' => [
                HavingColumnFilter::class,
                [
                    'column' => 'mileage',
                    'operator' => '<=',
                ]
            ],
            'min_price' => [
                HavingColumnFilter::class,
                [
                    'column' => 'min_price',
                    'operator' => '>=',
                ]
            ],
            'max_price' => [
                HavingColumnFilter::class,
                [
                    'column' => 'max_price',
                    'operator' => '>=',
                ]
            ],
            'kpp' => [
                EquipmendFieldLikeInFilter::class,
                [
                    'column' => 252,
                ]
            ],
            'engine_type' => [
                EquipmendFieldLikeInFilter::class,
                [
                    'column' => 37,
                ]
            ],
            'type_drive' => [
                EquipmendFieldLikeInFilter::class,
                [
                    'column' => 248,
                ]
            ],
            'min_engine_volume' => [
                EquipmendFieldWhereFilter::class,
                [
                    'column' => 253,
                    'operator' => '>=',
                ]
            ],
            'max_engine_volume' => [
                EquipmendFieldWhereFilter::class,
                [
                    'column' => 253,
                    'operator' => '<=',
                ]
            ],
            'min_engine_power' => [
                EquipmendFieldWhereFilter::class,
                [
                    'column' => 49,
                    'operator' => '>=',
                ]
            ],
            'max_engine_power' => [
                EquipmendFieldWhereFilter::class,
                [
                    'column' => 49,
                    'operator' => '<=',
                ]
            ],
        ];
    }
}
