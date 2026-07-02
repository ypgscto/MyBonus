<?php

namespace App\Http\Controllers;

use App\Enums\ReportType;
use App\Http\Requests\Report\GenerateReportRequest;
use App\Services\ReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ReportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
    ) {}

    public function index(GenerateReportRequest $request): View
    {
        $filterOptions = $this->reports->filterOptions($request->user());
        $filters = $request->validated();
        $type = isset($filters['type']) ? ReportType::from($filters['type']) : null;
        $result = $type ? $this->reports->generate($type, $filters, $request->user()) : null;

        return view('reports.index', [
            'filterOptions' => $filterOptions,
            'filters' => $filters,
            'type' => $type,
            'result' => $result,
        ]);
    }

    public function exportExcel(GenerateReportRequest $request): RedirectResponse
    {
        return back()->with('warning', 'Export Excel akan segera tersedia.');
    }

    public function exportPdf(GenerateReportRequest $request): RedirectResponse
    {
        return back()->with('warning', 'Export PDF akan segera tersedia.');
    }
}
