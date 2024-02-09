<?php

namespace App\Http\Controllers\Front\Used;

use App\Http\Controllers\Front\BaseController;
use App\Mediators\BrandMediator;
use App\Repositories\BrandRepository;
use App\Repositories\EquipmentRepository;
use App\Repositories\ModelRepository;
use Illuminate\Http\JsonResponse;
use App\Repositories\BlogRepository;

class IndexController extends BaseController
{
    private $brandRepo;
    private $modelRepo;
    private $equipmentRepo;
    private $filterlimit = 8;
    private $isNew = 2;

    public function __construct()
    {
        $this->brandRepo = new BrandRepository;
        $this->modelRepo = new ModelRepository;
        $this->equipmentRepo = new EquipmentRepository;
    }

    public function index()
    {

        if (request()->ajax()) {
            return $this->paginate();
        }
        
        $filterData = $this->getFilterData($this->isNew);
        $filteredModels = $this->filterModels($this->filterlimit, $this->isNew, true, 0);
        $filteredModelsImages = $this->setMedia(array_keys($filteredModels->keyBy('id')->toArray()), 4);

        $headerInversion = 'Y';

        return view(
            'front.used.index',
            compact(
                'filterData',
                'filteredModels',
                'filteredModelsImages'                
            )
        );
    }

    public function getModelsByBrandIds()
    {
        return response()->json(['data' => $this->getModelsByBrandIdsForFilter($this->isNew)]);
    }

    public function getFilteredCarsCount(): JsonResponse
    {
        if (!request()->ajax()) {
            return response()->json([], 404);
        }

        $total = $this->filterModels($this->filterlimit, $this->isNew, true, 0)->total();

        return response()->json(['data' => ['total' => $total ? $total : 0]]);
    }

    private function paginate(): JsonResponse
    {
        if (!request()->ajax()) {
            return response()->json([], 404);
        }

        $filteredModels = $this->filterModels($this->filterlimit, $this->isNew, true, 0);
        $filteredModelsImages = $this->setMedia(array_keys($filteredModels->keyBy('id')->toArray()), 4);
        //$filteredModels = $this->setImages($filteredModels);
        $total = $filteredModels->total();

        $notFound = ($total <= 0) ? true : false;

        $data = [
            'html' => '',
            'total' => $total,
            'has_more_pages' => $filteredModels->hasMorePages(),
        ];

        if ($filteredModels->count() > 0) {
            $page = request()->query('page');
            foreach ($filteredModels as $k => $v) {
                $data['html'] .= view('front.used._model-card', compact('v', 'filteredModelsImages'))->render();
            }
        } else {
            $data['html'] = 'По вашему запросу не найдено ни одного автомобиля';
        }

        return response()->json(compact('data'));
    }
}
