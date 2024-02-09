<?php
namespace App\Http\Controllers\Components\Filter;

use Illuminate\Support\Facades\Cache;
use App\Mediators\BodyTypeMediator;
use Illuminate\Support\Facades\DB;
use Samovar\VC\Models\Model;
use App\Helpers\MediaHelper;
use App\Repositories\BrandRepository;

class ModelFilterHelper
{
    public $models;

    private $projectId;
    public $isNew;
    private $isActive = true;
    private $isArchived = null;

    /**
     * Забираем все нужные модели для изначальной работы
     * Обычно, это активная неархивная модель нового авто
     */
    public function __construct(int $isNew = 1, bool $isActive = true, int $isArchived = 0)
    {

        $this->isNew = $isNew;
        //dd($this->isNew);
        $this->isActive = $isActive;
        $this->isArchived = $isArchived;
        $this->projectId = (int) env('PROJECT_ID');

        // TODO: Вынести аргументы в this
    }

    /**
     * Получаем разбег цен
     * TODO: Вынести в КЭШ
     */
    public function getPriceRange()
    {
        $cacheKey = 'catalog_filter_' . $this->isNew . '_data_price_range';
        if (Cache::has($cacheKey)) {
            $priceRange = Cache::get($cacheKey);
        } else {
            $priceRange = Model::select(
                DB::raw('min(price.price) as min_price'),
                DB::raw('max(rec_price) as max_price')
            )
                ->leftJoin('equipment', 'equipment.model_id', 'model.id')
                ->leftJoin('price', 'equipment.id', 'price.equipment_id')
                ->where('price', '>', 0)
                ->where('is_new', $this->isNew)
                ->where('project_model_rel.status', $this->isActive) // Сама таблица из GlobalScope
                ->where('project_model_rel.type', $this->isArchived) // Сама таблица из GlobalScope
                ->first()
                ->toArray();
            Cache::add($cacheKey, $priceRange, 60 * 60 * 24 * 7);

        }
        return $priceRange;
    }

    /**
     * Получаем простой список моделей по ID марок
     * TODO: Вынести в КЭШ
     */

    public function getModelsSimpleListbyBrandIds(array $brandIds)
    {
        if (empty($brandIds))
            return [];
        // TODO: Попробовать избавиться от подзапросов
        return Model::select([
            'model.title',
            'model.id',
            'brand.id as brand_id',
            'brand.title as brand_title'
        ])
            ->leftJoin('brand', 'brand.id', 'model.brand_id')
            ->where('is_new', $this->isNew)
            ->where('project_model_rel.status', $this->isActive) // Сама таблица из GlobalScope
            ->where('project_model_rel.type', $this->isArchived) // Сама таблица из GlobalScope
            ->whereIn('brand_id', $brandIds)
            ->orderBy('brand_id')
            ->get();
    }

    /**
     * Типы кузова
     */
    public function getBodyTypes()
    {
        return BodyTypeMediator::get($this->isNew);
    }

    /**
     * Типы трансмиссии
     */
    public function getConfigKppList()
    {
        return config('project.kpp_list');
    }

    public function getConfigEngineTypesList()
    {
        return config('project.engine_type_list');
    }

    public function getConfigTypeDriveList()
    {
        return config('project.type_drive_list');
    }

    public function getConfigEngineVolumeList()
    {
        return config('project.engine_volume_list');
    }

    public function getConfigEnginePowerList()
    {
        return config('project.engine_power_list');
    }

    /**
     * Типы кузова
     */
    public function getBrands()
    {
        $cacheKey = 'catalog_filter_' . $this->isNew . '_data_brands';
        if (Cache::has($cacheKey)) {
            $items = Cache::get($cacheKey);
        } else {
            $items = (new BrandRepository)->getHavingModels($this->isNew);
            if ($items->count() > 0) {
                $items = MediaHelper::addLogo($items)->toArray();
                $array = [];
                $items = array_merge($array, $items);
            } else {
                $items = [];
            }

            Cache::add($cacheKey, $items, 60 * 60 * 24 * 7);
        }

        return $items;
    }

    public function getYearRange() {
        $yearRange = [];
        $yearRange['min'] = $this->getMinModelSignedFieldValue(13);
        $yearRange['max'] = $this->getMaxModelSignedFieldValue(13);
        return  $yearRange;
    }

    public function getMileageRange() {
        $mileageRange = [];
        $mileageRange['min'] = $this->getMinModelSignedFieldValue(14);
        $mileageRange['max'] = $this->getMaxModelSignedFieldValue(14);
        return  $mileageRange;
    }
    
    public function getMinModelSignedFieldValue(int $fieldID)
    {
        return
            Model::selectRaw('MIN(CAST(model_field_rel.string_value AS SIGNED)) as min')
                ->where('model_field_rel.field_id', $fieldID)
                ->where('model.is_new', $this->isNew)
                ->where(['project_model_rel.status' => $this->isActive])
                ->leftJoin('model_field_rel', 'model.id', 'model_field_rel.model_id')->get()[0]['min'];
    }

    public function getMaxModelSignedFieldValue(int $fieldID)
    {
        return
            Model::selectRaw('MAX(CAST(model_field_rel.string_value AS SIGNED)) as max')
                ->leftJoin('model_field_rel', 'model.id', 'model_field_rel.model_id')
                ->where('model_field_rel.field_id', $fieldID)
                ->where(['project_model_rel.status' => $this->isActive])
                ->where('model.is_new', $this->isNew)
                ->get()[0]['max'];
    }

}
