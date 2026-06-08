<?php

namespace App\Http\Controllers;

use App\Services\ProductionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProgressController extends Controller
{
    public function __construct(
        private readonly ProductionService $productionService
    ) {}

    public function index(Request $request): Response
    {
        $workOrderId = $request->query('work_order_id');
        $logs = $this->productionService->getProgressLogs($workOrderId);

        return Inertia::render('Progress/Index', [
            'logs' => $logs,
        ]);
    }
}
