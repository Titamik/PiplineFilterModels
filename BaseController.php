<?php

namespace App\Http\Controllers\Front;

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
        //dd($isNew);
        $modelFilterHelper = new ModelFilterHelper($isNew);
        //dd($modelFilterHelper->isNew);
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

    public function filterModels(int $limit, int $isNew = 1, bool $isActive = true, int $isArchived = null)
    {
        $modelFilter = new ModelFilter($isNew, $isActive, $isArchived);
        $cacheKey = '';
        foreach (request()->all() as $key => $value) {
            switch ($key) {
                case 'brand_id':
                    $cacheKey .= 'brand_id:' . implode("-", $value);
                    break;
                case 'model_id':
                    $cacheKey .= 'model_id:' . implode("-", $value);
                    break;
                case 'body_type':
                    $cacheKey .= 'body_type:' . implode("-", $value);
                    break;
                case 'min_year':
                    $cacheKey .= 'min_year:' . $value;
                    break;
                case 'max_year':
                    $cacheKey .= 'max_year:' . $value;
                    break;
                case 'min_mileage':
                    $cacheKey .= 'min_mileage:' . $value;
                    break;
                case 'max_mileage':
                    $cacheKey .= 'max_mileage:' . $value;
                    break;
                case 'min_price':
                    $cacheKey .= 'min_price:' . $value;
                    break;
                case 'max_price':
                    $cacheKey .= 'max_price:' . $value;
                    break;
                case 'kpp':
                    $cacheKey .= 'brand_id:' . implode("-", $value);
                    break;
                case 'engine_type':
                    $cacheKey .= 'engine_type:' . implode("-", $value);
                    break;
                case 'type_drive':
                    $cacheKey .= 'type_drive:' . implode("-", $value);
                    break;
                case 'min_engine_volume':
                    $cacheKey .= 'min_engine_volume:' . $value;
                    break;
                case 'max_engine_volume':
                    $cacheKey .= 'max_engine_volume:' . $value;
                    break;
                case 'min_engine_power':
                    $cacheKey .= 'min_engine_power:' . $value;
                    break;
                case 'max_engine_power':
                    $cacheKey .= 'max_engine_power:' . $value;
                    break;
            }
        }
        $sort = request('sort_order', 'model.sort');
        $cacheKey .= 'sort_order:' . $sort;

        $hashKey = 'catalog_filter_' . $isNew . '_' . hash('md5', $cacheKey);
        if (Cache::has($hashKey)) {
            $sort = request('sort_order', 'model.sort');
            $builderModels = $modelFilter
                ->getByIds(Cache::get($hashKey))
                ->groupBy('model.id')
                ->orderByRaw($sort);
        } else {
            $modelFilter->init();
            $pipes = [];
            foreach (request()->all() as $key => $value) {
                switch ($key) {
                    case 'brand_id':
                        $pipes[] = new ColumnInFilter('brand_id', $value);
                        $cacheKey .= 'brand_id:' . implode("-", $value);
                        break;
                    case 'model_id':
                        $pipes[] = new ColumnInFilter('model.id', $value);
                        $cacheKey .= 'model_id:' . implode("-", $value);
                        break;
                    case 'body_type':
                        $pipes[] = new ColumnInFilter('body_type', $value);
                        $cacheKey .= 'body_type:' . implode("-", $value);
                        break;
                    case 'min_year':
                        $pipes[] = new HavingColumnFilter('year', '>=', $value);
                        $cacheKey .= 'min_year:' . $value;
                        break;
                    case 'max_year':
                        $pipes[] = new HavingColumnFilter('year', '<=', $value);
                        $cacheKey .= 'max_year:' . $value;
                        break;
                    case 'min_mileage':
                        $pipes[] = new HavingColumnFilter('mileage', '>=', $value);
                        $cacheKey .= 'min_mileage:' . $value;
                        break;
                    case 'max_mileage':
                        $pipes[] = new HavingColumnFilter('mileage', '<=', $value);
                        $cacheKey .= 'max_mileage:' . $value;
                        break;
                    case 'min_price':
                        $pipes[] = new HavingColumnFilter('min_price', '>=', $value);
                        $cacheKey .= 'min_price:' . $value;
                        break;
                    case 'max_price':
                        $pipes[] = new HavingColumnFilter('min_price', '<=', $value);
                        $cacheKey .= 'max_price:' . $value;
                        break;
                    case 'kpp':
                        $pipes[] = new EquipmendFieldLikeInFilter(252, $value);
                        $cacheKey .= 'brand_id:' . implode("-", $value);
                        break;
                    case 'engine_type':
                        $pipes[] = new EquipmendFieldLikeInFilter(37, $value);
                        $cacheKey .= 'engine_type:' . implode("-", $value);
                        break;
                    case 'type_drive':
                        $pipes[] = new EquipmendFieldLikeInFilter(248, $value);
                        $cacheKey .= 'type_drive:' . implode("-", $value);
                        break;
                    case 'min_engine_volume':
                        $pipes[] = new EquipmendFieldWhereFilter(253, '>=', $value);
                        $cacheKey .= 'min_engine_volume:' . $value;
                        break;
                    case 'max_engine_volume':
                        $pipes[] = new EquipmendFieldWhereFilter(253, '<=', $value);
                        $cacheKey .= 'max_engine_volume:' . $value;
                        break;
                    case 'min_engine_power':
                        $pipes[] = new EquipmentEnginePowerFilter(49, '>=', $value);
                        $cacheKey .= 'min_engine_power:' . $value;
                        break;
                    case 'max_engine_power':
                        $pipes[] = new EquipmentEnginePowerFilter(49, '<=', $value);
                        $cacheKey .= 'max_engine_power:' . $value;
                        break;
                }
            }
            $sort = request('sort_order', 'model.sort');
            $cacheKey .= 'sort_order:' . $sort;
            $pipes[] = new SortOrder($sort);

            $builderModels = $modelFilter->pipe($pipes)->groupBy('model.id');
            Cache::add($hashKey, $builderModels->pluck('model.id')->toArray(), 60 * 60 * 24 * 7);
        }


        return $builderModels->paginate($limit);
    }
}
