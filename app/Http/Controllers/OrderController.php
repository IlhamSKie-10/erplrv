<?php

namespace App\Http\Controllers;

use App\Services\OrderService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use App\Models\Order;
use App\Http\Requests\SaveOrderDraftRequest;
use App\Http\Requests\UpdateOrderRequest;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderService $orderService
    ) {}

    public function index(): Response
    {
        $orders = $this->orderService->getOrders();
        $accounts = $this->orderService->getCustomerAccounts();
        $cities = $this->orderService->getCities();
        $carriers = $this->orderService->getCarriers();

        return Inertia::render('CS/Index', [
            'orders' => $orders,
            'accounts' => $accounts,
            'cities' => $cities,
            'carriers' => $carriers,
        ]);
    }

    public function store(SaveOrderDraftRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $this->orderService->saveOrderDraft($validated, $request->user()->id);

        return redirect()->route('cs.index')->with('success', 'Pesanan berhasil disimpan.');
    }

    public function update(UpdateOrderRequest $request, Order $order): RedirectResponse
    {
        $validated = $request->validated();
        
        // Inject ID into payload for service
        $validated['id'] = $order->id;

        try {
            $this->orderService->saveOrderDraft($validated, $request->user()->id);
            return redirect()->back()->with('success', 'Pesanan berhasil diperbarui.');
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function submit(Request $request, Order $order): RedirectResponse
    {
        $this->orderService->submitOrder($order->id, $request->user()->id);
        return redirect()->back()->with('success', 'Pesanan berhasil disubmit ke Desain.');
    }

    public function destroy(Request $request, Order $order): RedirectResponse
    {
        $this->orderService->softDelete($order->id, $request->user()->id);
        return redirect()->back()->with('success', 'Pesanan berhasil dihapus.');
    }
}
