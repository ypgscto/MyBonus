<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\ReportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Report\GenerateReportRequest;
use App\Services\ReportService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function index(GenerateReportRequest $request): JsonResponse
    {
        $filterOptions = $this->reports->filterOptions($request->user());
        $filters = $request->validated();
        $type = isset($filters['type']) ? ReportType::from($filters['type']) : null;
        $result = $type ? $this->reports->generate($type, $filters, $request->user()) : null;

        return ApiResponse::success([
            'filter_options' => $filterOptions,
            'filters' => $filters,
            'type' => $type?->value,
            'result' => $result,
        ]);
    }
}
