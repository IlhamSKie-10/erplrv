<?php

namespace App\Http\Controllers;

use App\Models\ProductionWorkOrder;
use App\Services\ProductionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductionController extends Controller
{
    public function __construct(
        private readonly ProductionService $productionService
    ) {
    }

    public function index(Request $request): Response
    {
        $queueCode = $request->query('queue');
        $jobs = $this->productionService->getProductionJobs($queueCode);

        return Inertia::render('Production/Index', [
            'jobs' => $jobs,
            'activeQueue' => $queueCode,
        ]);
    }

    public function update(Request $request, ProductionWorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|string',
            'current_stage_id' => 'nullable|string',
            'currentStageId' => 'nullable|string',
            'assigned_personnel_id' => 'nullable|string',
            'assignedTo' => 'nullable|string',
            'blocked_reason' => 'nullable|string',
            'blocked_severity' => 'nullable|string',
            'dependencies_met' => 'nullable|boolean',
            'is_held' => 'nullable|boolean',
            'hold_reason' => 'nullable|string',
            'remaining_steps' => 'nullable|integer',
        ]);

        try {
            $this->productionService->updateProductionStatus($workOrder->id, $validated, $request->user()->id);

            return redirect()->back()->with('success', 'Status produksi diperbarui.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function logProgress(Request $request, ProductionWorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validate([
            'stage_id' => 'nullable|string',
            'stageId' => 'nullable|string',
            'current_stage_id' => 'nullable|string',
            'person' => 'nullable|string',
            'assignedTo' => 'nullable|string',
            'personnel_id' => 'nullable|string',
            'status' => 'required|string',
            'note' => 'nullable|string',
            'started_at' => 'nullable|date',
        ]);

        try {
            $this->productionService->addProgressLog($workOrder->id, $validated, $request->user()->id);

            return redirect()->back()->with('success', 'Log progres dicatat.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function pin(Request $request, ProductionWorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validate([
            'expires_at' => 'nullable|date',
            'manual_sort_index' => 'required|numeric',
        ]);

        $this->productionService->pinJob(
            $workOrder->id,
            $validated['expires_at'] ?? null,
            (float) $validated['manual_sort_index'],
            $request->user()->id
        );

        return redirect()->back()->with('success', 'Pekerjaan di-pin.');
    }

    public function hold(Request $request, ProductionWorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validate([
            'is_held' => 'required|boolean',
            'hold_reason' => 'nullable|string',
        ]);

        $this->productionService->holdJob(
            $workOrder->id,
            $validated['is_held'],
            $validated['hold_reason'] ?? null,
            $request->user()->id
        );

        return redirect()->back()->with('success', 'Status hold diperbarui.');
    }

    public function dependency(Request $request, ProductionWorkOrder $workOrder): RedirectResponse
    {
        $validated = $request->validate([
            'dependencies_met' => 'required|boolean',
            'blocked_reason' => 'nullable|string',
        ]);

        $this->productionService->markDependency(
            $workOrder->id,
            $validated['dependencies_met'],
            $request->user()->id,
            $validated['blocked_reason'] ?? null
        );

        return redirect()->back()->with('success', 'Status dependensi diperbarui.');
    }
}
