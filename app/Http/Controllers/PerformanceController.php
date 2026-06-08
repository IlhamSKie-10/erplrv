<?php

namespace App\Http\Controllers;

use App\Services\ProductionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PerformanceController extends Controller
{
    public function __construct(
        private readonly ProductionService $productionService
    ) {}

    public function index(): Response
    {
        $reports = $this->productionService->getPerformanceReports();

        return Inertia::render('Performance/Index', [
            'reports' => $reports,
        ]);
    }
}
