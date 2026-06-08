<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request): Response
    {
        $orderId = $request->query('order_id');
        $logs = $this->notificationService->getActivityLogs($orderId);

        return Inertia::render('Activity/Index', [
            'logs' => $logs,
        ]);
    }
}
