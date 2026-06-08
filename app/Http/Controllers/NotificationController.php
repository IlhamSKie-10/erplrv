<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request): Response
    {
        $notifications = $this->notificationService->getNotifications($request->user()->id);
        
        return Inertia::render('Notifications/Index', [
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $recipientId): RedirectResponse
    {
        $this->notificationService->markAsRead($recipientId, $request->user()->id);
        return redirect()->back();
    }

    public function markAllAsRead(Request $request): RedirectResponse
    {
        $this->notificationService->markAllAsRead($request->user()->id);
        return redirect()->back();
    }
}
