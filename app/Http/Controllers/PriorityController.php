<?php

namespace App\Http\Controllers;

use App\Services\ProductionService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PriorityController extends Controller
{
    public function __construct(
        private readonly ProductionService $productionService
    ) {}

    public function index(Request $request): Response
    {
        $queueCode = $request->query('queue');
        $jobs = $this->productionService->getProductionJobs($queueCode);

        return Inertia::render('Priority/Index', [
            'jobs' => $jobs,
        ]);
    }
}
