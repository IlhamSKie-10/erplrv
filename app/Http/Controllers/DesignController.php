<?php

namespace App\Http\Controllers;

use App\Models\DesignTask;
use App\Services\DesignService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignController extends Controller
{
    public function __construct(
        private readonly DesignService $designService
    ) {
    }

    public function index(): Response
    {
        $tasks = $this->designService->getDesignTasks();

        return Inertia::render('Designer/Index', [
            'tasks' => $tasks,
        ]);
    }

    public function update(Request $request, DesignTask $task): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'sometimes|string',
            'print_sticker' => 'nullable|string',
            'printSticker' => 'nullable|string',
            'cut_methods' => 'nullable|array',
            'cut_methods.*' => 'string',
            'cutMethods' => 'nullable|array',
            'cutMethods.*' => 'string',
            'assigned_designer_id' => 'nullable|string',
            'assigned_designer' => 'nullable|string',
            'assignedDesigner' => 'nullable|string',
            'design_acc_at' => 'nullable|date',
            'designAccAt' => 'nullable|date',
        ]);

        try {
            $this->designService->updateDesignTask($task->id, $validated, $request->user()->id);

            return redirect()->back()->with('success', 'Status desain berhasil diperbarui.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function forward(Request $request, DesignTask $task): RedirectResponse
    {
        try {
            $this->designService->forwardToProduction($task->id, $request->user()->id);

            return redirect()->back()->with('success', 'Pesanan berhasil diteruskan ke produksi.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
