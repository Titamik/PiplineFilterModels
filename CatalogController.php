<?php

namespace App\Http\Controllers\Front;

use App\Http\Request\FilterRequest;
use App\Repositories\EquipmentRepository;
use App\Repositories\ModelRepository;
use App\Repositories\PageRepository;
use Illuminate\Http\JsonResponse;
use App\Repositories\BlogRepository;
use App\Http\Controllers\Front\Components\Titamik\Filter\ModelFilterHelper;

class CatalogController extends BaseController
{
    private $pageRepo;
    private $modelRepo;
    private $equipmentRepo;
    private $filterNewlimit = 8;
    private $isNew = 2;
    private $modelFilterHelper;

    private $modelFilter;

    public function __construct()
    {
        $this->pageRepo = new PageRepository;
        $this->modelRepo = new ModelRepository;
        $this->equipmentRepo = new EquipmentRepository;
        $this->modelFilterHelper = new ModelFilterHelper;
        // $this->modelFilter = new ModelFilter;
    }

    public function index(FilterRequest $request)
    {
        if ($request->ajax()) {
            return $this->paginate();
        }

        // FilterData
        $filterData = $this->getFilterData();
        $filteredModels = $this->filterModels($request, $this->filterNewlimit);
        $filteredModels = $this->setImages($filteredModels);

        return view(
            'front.catalog.index',
            compact(
                'filteredModels',
                'filterData',
            )
        );
    }

    public function getModelsByBrandIds()
    {
        return response()->json(['data' => $this->getModelsByBrandIdsForFilter()]);
    }

    private function paginate(): JsonResponse
    {
        if (!request()->ajax()) {
            return response()->json([], 404);
        }

        $filteredModels = $this->filterModels($this->filterNewlimit);
        $filteredModels = $this->setImages($filteredModels);
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
                $data['html'] .= view('front.catalog._catalog-model', compact('v'))->render();
            }
        } else {
            $data['html'] = 'По вашему запросу не найдено ни одного автомобиля';
        }

        return response()->json(compact('data'));
    }


    public function getFilteredCarsCount(): JsonResponse
    {
        if (!request()->ajax()) {
            return response()->json([], 404);
        }

        $total = $this->filterModels($this->filterNewlimit)->total();

        return response()->json(['data' => ['total' => $total ? $total : 0]]);
    }
}
