<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use App\Models\Reminder;

class ReminderController extends Controller
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function index(Request $request): Response
    {
        $reminders = $this->notificationService->getReminders($request->user()->id);

        return Inertia::render('Reminders/Index', [
            'reminders' => $reminders,
        ]);
    }

    public function acknowledge(Request $request, Reminder $reminder): RedirectResponse
    {
        $this->notificationService->acknowledgeReminder($reminder->id, $request->user()->id);
        return redirect()->back();
    }
}
