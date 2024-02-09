<?php
namespace App\Http\Controllers\Components\Filter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Pipeline\Pipeline;
use Samovar\VC\Models\Equipment;


class ModelFilter
{
    private $models = null;
    private $projectId = null;
    private $isNew = 1;
    private $isActive = true;
    private $isArchived = null;

    public function __construct(int $isNew = 1, bool $isActive = true, int $isArchived = null)
    {
        $this->isNew = $isNew;
        $this->isActive = $isActive;
        $this->isArchived = $isArchived;
        $this->projectId = (int) env('PROJECT_ID');
    }

    /**
     * Забираем все нужные модели для изначальной работы
     * Обычно, это активная неархивная модель нового авто
     */
    public function init()
    {
        if ($this->models === null) {
            $this->models =
                Equipment::select([
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
                    where equipment.status = 1 and equipment.model_id = model.id) and price.project_id = {$this->projectId} and price.price > 0) as min_price"),
                    DB::raw('(select CONCAT_WS(",", media.id, media.object_type, media.type, media.path, media.title, media.alt)
                    from media where media.object_type = 2 and media.type = 2 and media.item_id = model.id and media.status = 1 limit 1) as media')
                ])
                    ->leftJoin('model', 'model.id', 'equipment.model_id')
                    ->leftJoin('project_model_rel', 'project_model_rel.model_id', 'model.id')
                    ->leftJoin('brand', 'brand.id', 'model.brand_id')
                    ->where('is_new', $this->isNew)
                    ->where('project_model_rel.status', $this->isActive); // Сама таблица из GlobalScope
            if ($this->isArchived !== null) {
                $this->models = $this->models->where('project_model_rel.type', $this->isArchived); // Сама таблица из GlobalScope;
            }
        }

        return $this->models;
    }

    public function pipe(array $pipelines = []): Builder
    {
        return app(Pipeline::class)
            ->send($this->models)
            ->through($pipelines)
            ->thenReturn();
    }

    public function getByIds(array $modelIds): Builder
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
            where equipment.status = 1 and equipment.model_id = model.id) and price.project_id = {$this->projectId} and price.price > 0) as min_price"),
            DB::raw('(select CONCAT_WS(",", media.id, media.object_type, media.type, media.path, media.title, media.alt)
            from media where media.object_type = 2 and media.type = 2 and media.item_id = model.id and media.status = 1 limit 1) as media')
        ])
            ->leftJoin('model', 'model.id', 'equipment.model_id')
            ->leftJoin('project_model_rel', 'project_model_rel.model_id', 'model.id')
            ->leftJoin('brand', 'brand.id', 'model.brand_id')
            ->whereIn('model.id', $modelIds);
    }

}
