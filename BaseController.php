<?php

namespace App\Http\Controllers\Front;

use App\Http\Request\FilterRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Http\Controllers\Controller;

use App\Http\Controllers\Components\Filter\ModelFilter;
use App\Http\Controllers\Components\Filter\ModelFilterHelper;
use App\Http\Controllers\Components\Filter\Piplines\SortOrder;
use App\Http\Controllers\Components\Filter\Piplines\ColumnInFilter;
use App\Http\Controllers\Components\Filter\Piplines\ColumnWhereFilter;
use App\Http\Controllers\Components\Filter\Piplines\HavingColumnFilter;
use App\Http\Controllers\Components\Filter\Piplines\EquipmendFieldWhereFilter;
use App\Http\Controllers\Components\Filter\Piplines\EquipmendFieldLikeInFilter;
use App\Http\Controllers\Components\Filter\Piplines\EquipmentEnginePowerFilter;

class BaseController extends Controller
{
    protected const CACHE_TTL = 60 * 60 * 24 * 7;

    /* FILTER */

    protected function getFilterData(int $isNew = 1)
    {
        $modelFilterHelper = new ModelFilterHelper($isNew);
        $filterData = [];
        $filterData['Brands'] = $modelFilterHelper->getBrands();
        $filterData['Models'] = $this->getModelsByBrandIdsForFilter();
        $filterData['PriceRange'] = $modelFilterHelper->getPriceRange();
        $filterData['BodyTypes'] = $modelFilterHelper->getBodyTypes();
        $filterData['KppList'] = $modelFilterHelper->getConfigKppList();
        $filterData['EngineTypesList'] = $modelFilterHelper->getConfigEngineTypesList();
        $filterData['TypeDriveList'] = $modelFilterHelper->getConfigTypeDriveList();
        $filterData['EngineVolumeList'] = $modelFilterHelper->getConfigEngineVolumeList();
        $filterData['EnginePowerList'] = $modelFilterHelper->getConfigEnginePowerList();

        if ($isNew == 2) {
            $filterData['YearRange'] = $modelFilterHelper->getYearRange();
            $filterData['MileageRange'] = $modelFilterHelper->getMileageRange();
        }

        return $filterData;
    }

    public function getModelsByBrandIdsForFilter(int $isNew = 1)
    {
        $modelFilterHelper = new ModelFilterHelper($isNew);
        $brandIds = request('brand_id', []);
        $models = $modelFilterHelper->getModelsSimpleListbyBrandIds($brandIds);

        $optgroups = [];
        $currentBrand = '';
        $optgroups = [];
        $data = [];

        foreach ($models as $model) {
            if ($currentBrand != $model->brand_id) {
                $optgroups[$model->brand_id]['label'] = $model->brand_title;
            }
            $optgroups[$model->brand_id]['options'][] = ['text' => $model->title, 'value' => $model->id];
        }

        foreach ($optgroups as $optgroup) {
            $data[] = $optgroup;
        }

        return $data;
    }

    public function filterModels(FormRequest $request,
                                 int $limit,
                                 int $isNew = 1,
                                 bool $isActive = true,
                                 int $isArchived = null
    )
    {
        return static::generateBuilder(...func_get_args())->paginate($limit);
    }

    // Хелпер для генерации ID кеша по массиву
    protected static function requestCacheId(array $attributes, string $prefix): string
    {
        $cacheAttributes = $attributes;
        ksort($cacheAttributes); // Сортируем ключи

        foreach($cacheAttributes as $key => $value) {
            if (is_array($value)) { // Если значение массив, то сортируем значения в нем в одном порядке
                sort($cacheAttributes[$key]);
            }
        }

        return $prefix . md5(serialize($cacheAttributes));
    }

    protected static function generateBuilder(FormRequest $request,
                                              int $limit,
                                              int $isNew = 1,
                                              bool $isActive = true,
                                              int $isArchived = null
    ): Builder
    {
        $modelFilter = new ModelFilter($isNew, $isActive, $isArchived);
        $cacheId = static::requestCacheId((array) $request->validated(), "catalog_filter_{$isNew}_");
        if ($modelCachedPrimary = Cache::get($cacheId)) {
            $builder = $modelFilter
                ->getByModelsIds($modelCachedPrimary, $request->get('sort_order'));
        } else {

            $pipelines = $modelFilter->getPipelines($request->validated());
            $pipelines[] = new SortOrder($request->get('sort_order'));

            $builder = $modelFilter->pipe($pipelines)->groupBy('model.id');
            Cache::put($cacheId, $builder->pluck('model.id')->all(), static::CACHE_TTL);
        }

        return $builder;
    }
}
