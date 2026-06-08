<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Carrier;
use App\Models\CustomerAccount;
use App\Models\DesignTask;
use App\Models\Order;
use App\Models\OrderSource;
use App\Models\Personnel;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductModel;
use App\Models\ProductType;
use App\Enums\OrderStatus;
use App\Enums\PaymentType;
use App\Enums\PaymentStatus;
use App\Enums\PackingType;
use App\Enums\DesignStatus;
use Illuminate\Support\Facades\DB;

class OrderService
{
    private const CATALOG_CONFIG = [
        [
            'id' => 'advertising-1',
            'label' => 'Advertising 1',
            'products' => [
                [
                    'id' => 'neon-box',
                    'label' => 'Neon Box',
                    'queue' => 'ADVERTISING_1',
                    'variants' => [
                        ['id' => '1-sisi', 'label' => '1 Sisi'],
                        ['id' => '2-sisi', 'label' => '2 Sisi'],
                    ],
                    'shapes' => [
                        ['id' => 'bulat', 'label' => 'Bulat'],
                        ['id' => 'kotak', 'label' => 'Kotak'],
                    ],
                    'brackets' => [
                        ['id' => 'bawah', 'label' => 'Bawah'],
                        ['id' => 'samping', 'label' => 'Samping'],
                    ],
                    'lamps' => [
                        ['id' => 'dengan-lampu', 'label' => 'Dengan Lampu'],
                        ['id' => 'tanpa-lampu', 'label' => 'Tanpa Lampu'],
                    ],
                ],
            ],
        ],
        [
            'id' => 'advertising-2',
            'label' => 'Advertising 2',
            'products' => [
                ['id' => 'letter-3d', 'label' => 'Letter 3D', 'queue' => 'ADVERTISING_2'],
                ['id' => 'logo-akrilik', 'label' => 'Logo Akrilik', 'queue' => 'ADVERTISING_2'],
                ['id' => 'neonflex', 'label' => 'Neonflex', 'queue' => 'ADVERTISING_2'],
            ],
        ],
        [
            'id' => 'home-decor',
            'label' => 'Home Decor',
            'products' => [
                ['id' => 'logo-kayu', 'label' => 'Logo Kayu', 'queue' => 'HOMEDECOR'],
                ['id' => 'token', 'label' => 'Token', 'queue' => 'HOMEDECOR'],
                ['id' => 'papan-nomor-rumah', 'label' => 'Papan Nomor Rumah', 'queue' => 'HOMEDECOR'],
            ],
        ],
        [
            'id' => 'logo-ukir',
            'label' => 'Logo & Tulisan Ukir',
            'products' => [
                ['id' => 'logo-tulisan-ukir', 'label' => 'Logo & Tulisan Ukir', 'queue' => 'LOGO_UKIR'],
            ],
        ],
    ];

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Expose the catalog config to Livewire components / views.
     */
    public function getCatalogConfig(): array
    {
        return self::CATALOG_CONFIG;
    }

    public function generateOrderCode(): string
    {
        $now = now();
        $month = $now->month;            // 1–12 (tanpa leading zero, mudah diucapkan)
        $startOfMonth = $now->copy()->startOfMonth();

        // Ambil order terakhir di bulan ini dan ekstrak nomor urut
        $lastOrder = Order::where('created_at', '>=', $startOfMonth)
            ->orderBy('created_at', 'desc')
            ->first();

        $sequence = 1;
        if ($lastOrder?->order_code) {
            // Format baru: "01-6", "02-6", dsb.
            if (preg_match('/^(\d+)-(\d+)$/', $lastOrder->order_code, $matches)) {
                $sequence = ((int) $matches[1]) + 1;
            }
        }

        // Format: {urutan 2 digit}-{bulan}  contoh: 01-6, 02-6, 13-12
        return str_pad((string) $sequence, 2, '0', STR_PAD_LEFT) . '-' . $month;
    }

    public function buildFormSnapshot(array $input): array
    {
        $snapshot = [
            'size' => $input['size'] ?? null,
            'variant' => $input['variant'] ?? null,
            'shape' => $input['shape'] ?? null,
            'bracket' => $input['bracket'] ?? null,
            'lamp' => $input['lamp'] ?? null,
            'productionNotes' => $input['productionNotes'] ?? ($input['production_notes'] ?? null),
            'referenceLink' => $input['referenceLink'] ?? ($input['reference_link'] ?? null),
            'specialRequest' => $input['specialRequest'] ?? ($input['special_request'] ?? null),
            'text' => $input['text'] ?? null,
            'color' => $input['color'] ?? null,
        ];

        return array_filter($snapshot, fn ($value) => filled($value));
    }

    public function saveOrderDraft(array $input, string $actorUserId): array
    {
        return DB::transaction(function () use ($input, $actorUserId) {
            $categoryConfig = $this->findCategoryConfig($input['product_type_id']);
            $productConfig = $this->findProductConfig($input['product_type_id'], $input['product_id']);

            $orderSource = OrderSource::firstOrCreate(
                ['code' => $input['order_source_id']],
                ['name' => strtoupper($input['order_source_id'])]
            );

            $productType = ProductType::firstOrCreate(
                ['code' => $input['product_type_id']],
                ['name' => $categoryConfig['label'] ?? strtoupper($input['product_type_id'])]
            );

            $product = Product::firstOrCreate(
                ['code' => $input['product_id']],
                [
                    'name' => $productConfig['label'] ?? strtoupper($input['product_id']),
                    'product_type_id' => $productType->id,
                    'category_id' => $this->ensureGeneralCategory(),
                    'production_queue' => $productConfig['queue'] ?? ($input['production_queue'] ?? 'ADVERTISING_1'),
                ]
            );

            $productModelId = null;
            if (!empty($input['model_id'])) {
                $productModel = ProductModel::firstOrCreate(
                    ['code' => $input['model_id']],
                    ['name' => strtoupper($input['model_id']), 'product_type_id' => $productType->id]
                );
                $productModelId = $productModel->id;
            }

            $expeditionId = null;
            if (!empty($input['expedition_id'])) {
                $carrier = Carrier::firstOrCreate(
                    ['code' => $input['expedition_id']],
                    ['name' => strtoupper($input['expedition_id'])]
                );
                $expeditionId = $carrier->id;
            }

            $account = CustomerAccount::where('name', $input['account_name'])->first();
            if (!$account) {
                $account = CustomerAccount::create([
                    'code' => 'account-' . time(),
                    'name' => $input['account_name'],
                ]);
            }

            $nextStatus = $this->normalizeOrderStatus($input['status'] ?? null);
            $nextDesignStatus = $this->normalizeDesignStatus($input['design_status'] ?? ($input['designStatus'] ?? null));
            $formSnapshot = $this->buildFormSnapshot($input);

            if (!empty($input['id'])) {
                $updated = Order::where('id', $input['id'])
                    ->where('version', $input['version'])
                    ->first();

                if (!$updated) {
                    throw new \RuntimeException(
                        'Pembaruan gagal. Pesanan telah diubah oleh orang lain pada saat yang sama. Harap muat ulang halaman.'
                    );
                }

                $updated->update([
                    'order_source_id' => $orderSource->id,
                    'account_id' => $account->id,
                    'product_id' => $product->id,
                    'product_model_id' => $productModelId,
                    'product_type_id' => $productType->id,
                    'deadline_at' => $input['deadline_at'],
                    'status' => $nextStatus,
                    'payment_type' => $this->normalizePaymentType($input['payment_type'] ?? ($input['paymentType'] ?? null)),
                    'total_order' => $input['total_order'],
                    'payment_status' => $this->normalizePaymentStatus($input['payment_status'] ?? ($input['paymentStatus'] ?? null)),
                    'amount_paid' => $input['amount_paid'],
                    'design_status' => $nextDesignStatus,
                    'packing_type' => $this->normalizePackingType($input['packing_type'] ?? ($input['packingType'] ?? null)),
                    'product_sentence' => $input['product_sentence'],
                    'admin_notes' => $input['admin_notes'] ?? null,
                    'form_snapshot' => $formSnapshot ?: null,
                    'expedition_id' => $expeditionId,
                    'city' => $input['city'] ?? null,
                    'version' => DB::raw('version + 1'),
                ]);

                $updated->refresh();

                if ($nextStatus !== 'DRAFT') {
                    $this->upsertDesignTask($updated->id, $nextDesignStatus);
                }

                AuditLog::create([
                    'actor_user_id' => $actorUserId,
                    'entity_type' => 'order',
                    'entity_id' => $updated->id,
                    'action' => 'UPDATE',
                    'summary' => "Updated order {$updated->order_code}",
                ]);

                return ['success' => true, 'order_id' => $updated->id, 'version' => $updated->version];
            }

            $orderCode = $this->generateOrderCode();
            $created = Order::create([
                'order_code' => $orderCode,
                'created_by_id' => $actorUserId,
                'order_source_id' => $orderSource->id,
                'account_id' => $account->id,
                'product_id' => $product->id,
                'product_model_id' => $productModelId,
                'product_type_id' => $productType->id,
                'deadline_at' => $input['deadline_at'],
                'status' => $nextStatus,
                'payment_type' => $this->normalizePaymentType($input['payment_type'] ?? ($input['paymentType'] ?? null)),
                'total_order' => $input['total_order'],
                'payment_status' => $this->normalizePaymentStatus($input['payment_status'] ?? ($input['paymentStatus'] ?? null)),
                'amount_paid' => $input['amount_paid'],
                'design_status' => $nextDesignStatus,
                'packing_type' => $this->normalizePackingType($input['packing_type'] ?? ($input['packingType'] ?? null)),
                'product_sentence' => $input['product_sentence'],
                'admin_notes' => $input['admin_notes'] ?? null,
                'form_snapshot' => $formSnapshot ?: null,
                'expedition_id' => $expeditionId,
                'city' => $input['city'] ?? null,
            ]);

            if ($nextStatus !== 'DRAFT') {
                $this->upsertDesignTask($created->id, $nextDesignStatus);
            }

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'order',
                'entity_id' => $created->id,
                'action' => 'CREATE',
                'summary' => "Created draft order {$orderCode}",
            ]);

            return ['success' => true, 'order_id' => $created->id, 'version' => $created->version];
        }, 10);
    }

    public function submitOrder(string $orderId, string $actorUserId): array
    {
        return DB::transaction(function () use ($orderId, $actorUserId) {
            $existingOrder = Order::findOrFail($orderId);
            $csMarkedAcc = $existingOrder->design_status === DesignStatus::ACC;

            $existingOrder->update([
                'status' => $csMarkedAcc ? OrderStatus::DESIGN_APPROVED : OrderStatus::CONFIRMED,
                'design_status' => $csMarkedAcc ? DesignStatus::ACC : DesignStatus::PROCESS,
                'submitted_at' => now(),
                'version' => $existingOrder->version + 1,
            ]);

            $existingOrder->refresh();

            $this->upsertDesignTask($orderId, $csMarkedAcc ? DesignStatus::ACC : DesignStatus::PROCESS);

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'order',
                'entity_id' => $orderId,
                'action' => 'SUBMIT',
                'summary' => "Submitted order {$existingOrder->order_code} to designer queue",
            ]);

            $this->notificationService->sendByRole(
                'Pesanan Baru',
                "Pesanan {$existingOrder->order_code} telah masuk dan siap didesain.",
                'DESIGNER',
                $orderId,
                'INFO'
            );

            // Filament Native Database Notification
            $designers = \App\Models\User::whereHas('roles', fn($q) => $q->where('code', 'DESIGNER'))->get();
            if ($designers->isNotEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->title('Pesanan Baru')
                    ->body("Pesanan {$existingOrder->order_code} telah masuk ke antrean desain.")
                    ->info()
                    ->sendToDatabase($designers);
            }

            return ['success' => true];
        }, 10);
    }

    public function duplicateOrder(string $orderId, string $actorUserId): Order
    {
        return DB::transaction(function () use ($orderId, $actorUserId) {
            $record = Order::findOrFail($orderId);
            $new = $record->replicate(['order_code', 'submitted_at', 'created_at', 'updated_at']);
            $new->status     = OrderStatus::DRAFT;
            $new->order_code = 'ORD-' . strtoupper(substr(uniqid(), -6));
            $new->save();

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'order',
                'entity_id' => $new->id,
                'action' => 'DUPLICATE',
                'summary' => "Duplicated from order {$record->order_code}",
            ]);

            return $new;
        }, 3);
    }

    public function softDelete(string $orderId, string $actorUserId): array
    {
        return DB::transaction(function () use ($orderId, $actorUserId) {
            Order::where('id', $orderId)->update(['deleted_at' => now()]);

            AuditLog::create([
                'actor_user_id' => $actorUserId,
                'entity_type' => 'order',
                'entity_id' => $orderId,
                'action' => 'SOFT_DELETE',
                'summary' => "Soft deleted order {$orderId}",
            ]);

            return ['success' => true];
        }, 10);
    }

    public function getOrders(): array
    {
        $orders = Order::whereNull('deleted_at')
            ->with([
                'orderSource',
                'account',
                'product',
                'productModel',
                'productType',
                'expedition',
                'createdBy',
                'designTasks' => fn ($query) => $query->orderBy('created_at')->select('id', 'order_id', 'design_acc_at'),
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return $orders->map(function (Order $order) {
            $snapshot = $this->normalizeFormSnapshot($order->form_snapshot);
            if (!$snapshot) {
                $snapshot = $this->deriveLegacyFormSnapshot(
                    $order->productType?->code ?? '',
                    $order->product?->code ?? '',
                    $order->product_sentence ?? '',
                    $order->admin_notes
                );
            }

            return [
                'id' => $order->id,
                'orderCode' => $order->order_code,
                'timestamp' => $order->created_at?->toISOString(),
                'admin' => $order->createdBy?->full_name ?? '',
                'orderSourceId' => $order->orderSource?->code ?? '',
                'accountName' => $order->account?->name ?? '',
                'productId' => $order->product?->code ?? '',
                'modelId' => $order->productModel?->code ?? '',
                'productTypeId' => $order->productType?->code ?? '',
                'deadlineAt' => $order->deadline_at?->toISOString(),
                'city' => $order->city ?? '',
                'expeditionId' => $order->expedition?->code ?? '',
                'packingType' => $this->mapPackingType($order->packing_type?->value),
                'status' => $this->mapOrderStatus($order->status?->value),
                'paymentType' => $this->mapPaymentType($order->payment_type?->value),
                'totalOrder' => (float) $order->total_order,
                'paymentStatus' => $this->mapPaymentStatus($order->payment_status?->value),
                'amountPaid' => (float) $order->amount_paid,
                'designStatus' => $this->mapDesignStatus($order->design_status?->value),
                'adminNotes' => $order->admin_notes ?? '',
                'productSentence' => $order->product_sentence,
                'queueId' => $this->mapQueueCode($order->product?->production_queue?->value),
                'version' => $order->version,
                'draftSavedAt' => $order->updated_at?->toISOString(),
                'designAccAt' => \Carbon\Carbon::parse($order->designTasks->first()?->getRawOriginal('design_acc_at') ?? null)?->toISOString(),
                'formSnapshot' => $this->formatSnapshotForFrontend($snapshot),
            ];
        })->toArray();
    }

    public function getCustomerAccounts(): array
    {
        return CustomerAccount::orderBy('name')->pluck('name')->toArray();
    }

    public function getCities(): array
    {
        return Order::whereNotNull('city')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->filter(fn ($city) => trim((string) $city) !== '')
            ->values()
            ->toArray();
    }

    public function getCarriers(): array
    {
        return Carrier::orderBy('name')
            ->get()
            ->map(fn (Carrier $carrier) => [
                'id' => $carrier->id,
                'code' => $carrier->code,
                'name' => $carrier->name,
            ])->toArray();
    }

    private function upsertDesignTask(string $orderId, DesignStatus|string $designStatus): void
    {
        $statusValue = $designStatus instanceof DesignStatus ? $designStatus->value : $designStatus;
        $existingTask = DesignTask::where('order_id', $orderId)->first();
        $shouldNotify = $statusValue === 'ACC' && (!$existingTask || $existingTask->status?->value !== 'ACC');

        // Business logic tanggal ACC:
        // - CS pilih ACC  → set design_acc_at = sekarang (atau pertahankan jika sudah ada)
        // - CS pilih PROCESS → null-kan design_acc_at agar designer melihat "-"
        $designAccAt = null;
        if ($statusValue === 'ACC') {
            $designAccAt = ($existingTask && $existingTask->status?->value === 'ACC' && filled($existingTask->getRawOriginal('design_acc_at')))
                ? \Carbon\Carbon::parse($existingTask->getRawOriginal('design_acc_at'))
                : now();
        }
        // Jika PROCESS → $designAccAt tetap null, sehingga designer melihat kolom kosong ("-")

        if ($existingTask) {
            $existingTask->update([
                'status'        => $statusValue,
                'design_acc_at' => $designAccAt,
            ]);
        } else {
            DesignTask::create([
                'order_id'            => $orderId,
                'assigned_designer_id' => null,
                'status'              => $statusValue,
                'design_acc_at'       => $designAccAt,
            ]);
        }

        if ($shouldNotify) {
            $this->notificationService->sendByRole(
                'Desain ACC',
                'Desain pesanan siap untuk diproduksi.',
                'PRODUCTION',
                $orderId,
                'INFO'
            );
        }
    }

    private function normalizeFormSnapshot(mixed $snapshot): ?array
    {
        if (!is_array($snapshot)) {
            return null;
        }

        $normalized = [
            'size' => $this->stringOrNull($snapshot['size'] ?? null),
            'variant' => $this->stringOrNull($snapshot['variant'] ?? null),
            'shape' => $this->stringOrNull($snapshot['shape'] ?? null),
            'bracket' => $this->stringOrNull($snapshot['bracket'] ?? null),
            'lamp' => $this->stringOrNull($snapshot['lamp'] ?? null),
            'productionNotes' => $this->stringOrNull($snapshot['productionNotes'] ?? ($snapshot['production_notes'] ?? null)),
            'referenceLink' => $this->stringOrNull($snapshot['referenceLink'] ?? ($snapshot['reference_link'] ?? null)),
            'specialRequest' => $this->stringOrNull($snapshot['specialRequest'] ?? ($snapshot['special_request'] ?? null)),
            'text' => $this->stringOrNull($snapshot['text'] ?? null),
            'color' => $this->stringOrNull($snapshot['color'] ?? null),
        ];

        $normalized = array_filter($normalized, fn ($value) => filled($value));

        return $normalized ?: null;
    }

    private function deriveLegacyFormSnapshot(
        string $productTypeId,
        string $productId,
        string $productSentence,
        ?string $adminNotes,
    ): ?array {
        $snapshot = [];
        $parts = explode(' - ', $productSentence);

        if (count($parts) >= 5) {
            $snapshot['size'] = isset($parts[1]) && $parts[1] !== '-' ? preg_replace('/ cm$/', '', $parts[1]) : null;
            $snapshot['text'] = isset($parts[2]) && $parts[2] !== '-' ? $parts[2] : null;
            $snapshot['lamp'] = isset($parts[4]) && $parts[4] !== '-' ? $parts[4] : null;

            $combo = isset($parts[3]) && $parts[3] !== '-'
                ? array_values(array_filter(explode('/', $parts[3])))
                : [];

            $productConfig = $this->findProductConfig($productTypeId, $productId);
            foreach ($combo as $item) {
                if ($this->configOptionExists($productConfig, 'variants', $item)) {
                    $snapshot['variant'] = $item;
                    continue;
                }
                if ($this->configOptionExists($productConfig, 'shapes', $item)) {
                    $snapshot['shape'] = $item;
                    continue;
                }
                if (empty($snapshot['color'])) {
                    $snapshot['color'] = $item;
                }
            }
        }

        foreach (preg_split("/\r\n|\n|\r/", (string) ($adminNotes ?? '')) as $line) {
            if (str_starts_with($line, 'Produksi: ')) {
                $snapshot['productionNotes'] = substr($line, strlen('Produksi: '));
            }
            if (str_starts_with($line, 'Link Ref: ')) {
                $snapshot['referenceLink'] = substr($line, strlen('Link Ref: '));
            }
            if (str_starts_with($line, 'Khusus: ')) {
                $snapshot['specialRequest'] = substr($line, strlen('Khusus: '));
            }
        }

        $snapshot = array_filter($snapshot, fn ($value) => filled($value));

        return $snapshot ?: null;
    }

    private function formatSnapshotForFrontend(?array $snapshot): ?array
    {
        if (!$snapshot) {
            return null;
        }

        return array_merge($snapshot, [
            'production_notes' => $snapshot['productionNotes'] ?? null,
            'reference_link' => $snapshot['referenceLink'] ?? null,
            'special_request' => $snapshot['specialRequest'] ?? null,
        ]);
    }

    private function findCategoryConfig(string $categoryId): ?array
    {
        foreach (self::CATALOG_CONFIG as $category) {
            if ($category['id'] === $categoryId) {
                return $category;
            }
        }

        return null;
    }

    private function findProductConfig(string $categoryId, string $productId): ?array
    {
        $category = $this->findCategoryConfig($categoryId);
        foreach ($category['products'] ?? [] as $product) {
            if ($product['id'] === $productId) {
                return $product;
            }
        }

        return null;
    }

    private function configOptionExists(?array $productConfig, string $key, string $id): bool
    {
        foreach ($productConfig[$key] ?? [] as $option) {
            if (($option['id'] ?? null) === $id) {
                return true;
            }
        }

        return false;
    }

    private function ensureGeneralCategory(): string
    {
        return ProductCategory::firstOrCreate(
            ['code' => 'general'],
            ['name' => 'General']
        )->id;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? $value : null;
    }

    private function mapOrderStatus(?string $status): string
    {
        return match ($status) {
            'DRAFT' => 'Draft',
            'CONFIRMED' => 'Confirmed',
            'DESIGN_IN_PROGRESS' => 'Design-in-progress',
            'DESIGN_APPROVED' => 'Design-approved',
            'IN_PRODUCTION' => 'In-production',
            'READY_TO_SHIP' => 'Ready-to-ship',
            'SHIPPED' => 'Shipped',
            'COMPLETED' => 'Completed',
            'CANCELLED' => 'Cancelled',
            'ON_HOLD' => 'On-hold',
            default => 'Draft',
        };
    }

    private function mapPaymentType(?string $type): string
    {
        return match ($type) {
            'NON_COD' => 'NON-COD',
            default => $type ?? 'SPL',
        };
    }

    private function mapPaymentStatus(?string $status): string
    {
        return match ($status) {
            'UNPAID' => 'Belum Bayar',
            'DP' => 'DP',
            'LUNAS' => 'Lunas',
            default => 'Belum Bayar',
        };
    }

    private function mapPackingType(?string $type): string
    {
        return match ($type) {
            'BUBBLE' => 'Bubble',
            'TRIPLEK' => 'Triplek',
            'KAYU' => 'Kayu',
            default => 'Bubble',
        };
    }

    private function mapDesignStatus(?string $status): string
    {
        return $status === 'ACC' ? 'Acc' : 'Process';
    }

    private function normalizeOrderStatus(mixed $status): OrderStatus
    {
        if ($status instanceof OrderStatus) return $status;
        
        if (!is_string($status) || trim($status) === '') {
            return OrderStatus::DRAFT;
        }

        return match ($status) {
            'Draft', 'DRAFT' => OrderStatus::DRAFT,
            'Confirmed', 'CONFIRMED' => OrderStatus::CONFIRMED,
            'Design-in-progress', 'DESIGN_IN_PROGRESS' => OrderStatus::DESIGN_IN_PROGRESS,
            'Design-approved', 'DESIGN_APPROVED' => OrderStatus::DESIGN_APPROVED,
            'In-production', 'IN_PRODUCTION' => OrderStatus::IN_PRODUCTION,
            'Ready-to-ship', 'READY_TO_SHIP' => OrderStatus::READY_TO_SHIP,
            'Shipped', 'SHIPPED' => OrderStatus::SHIPPED,
            'Completed', 'COMPLETED' => OrderStatus::COMPLETED,
            'Cancelled', 'CANCELLED' => OrderStatus::CANCELLED,
            'On-hold', 'ON_HOLD' => OrderStatus::ON_HOLD,
            default => OrderStatus::DRAFT,
        };
    }

    private function normalizePaymentType(mixed $type): PaymentType
    {
        if ($type instanceof PaymentType) return $type;
        
        if (!is_string($type) || trim($type) === '') {
            return PaymentType::SPL;
        }

        return match ($type) {
            'NON-COD', 'NON_COD' => PaymentType::NON_COD,
            'COD' => PaymentType::COD,
            default => PaymentType::SPL,
        };
    }

    private function normalizePaymentStatus(mixed $status): PaymentStatus
    {
        if ($status instanceof PaymentStatus) return $status;
        
        if (!is_string($status) || trim($status) === '') {
            return PaymentStatus::UNPAID;
        }

        return match ($status) {
            'DP' => PaymentStatus::DP,
            'Lunas', 'LUNAS' => PaymentStatus::LUNAS,
            default => PaymentStatus::UNPAID,
        };
    }

    private function normalizePackingType(mixed $type): PackingType
    {
        if ($type instanceof PackingType) return $type;
        
        if (!is_string($type) || trim($type) === '') {
            return PackingType::BUBBLE;
        }

        return match ($type) {
            'Triplek', 'TRIPLEK' => PackingType::TRIPLEK,
            'Kayu', 'KAYU' => PackingType::KAYU,
            default => PackingType::BUBBLE,
        };
    }

    private function normalizeDesignStatus(mixed $status): DesignStatus
    {
        if ($status instanceof DesignStatus) return $status;
        
        return $status === 'Acc' || $status === 'ACC' ? DesignStatus::ACC : DesignStatus::PROCESS;
    }

    private function mapQueueCode(?string $code): string
    {
        return match ($code) {
            'ADVERTISING_1' => 'advertising-1',
            'ADVERTISING_2' => 'advertising-2',
            'HOMEDECOR' => 'homedecor',
            'LOGO_UKIR' => 'logo-ukir',
            default => 'advertising-1',
        };
    }
}
