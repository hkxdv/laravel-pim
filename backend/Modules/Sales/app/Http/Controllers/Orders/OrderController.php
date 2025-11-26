<?php

declare(strict_types=1);

namespace Modules\Sales\App\Http\Controllers\Orders;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Inventory\App\Models\Product;
use Modules\Inventory\App\Models\StockMovement;
use Modules\Sales\App\Http\Requests\CreateSalesOrderRequest;
use Modules\Sales\App\Http\Requests\DeliverOrderRequest;
use Modules\Sales\App\Models\SalesItem;
use Modules\Sales\App\Models\SalesOrder;

/**
 * Controlador HTTP para operaciones de órdenes.
 *
 * Expone endpoints para listar, crear, actualizar estados, cancelar y entregar
 * órdenes, incluyendo deducción de stock y registro de movimientos.
 */
final class OrderController extends Controller
{
    /**
     * Lista órdenes con paginación y filtro opcional por estado.
     */
    public function index(Request $request): JsonResponse
    {
        $this->requireStaffUser($request);

        $status = (string) $request->query('status', '');
        $perPage = (int) $request->query('per_page', 15);

        $query = SalesOrder::query()->with('items');
        if ($status !== '') {
            $query->where('status', $status);
        }

        $p = $query->latest()->paginate($perPage)->appends($request->query());

        return response()->json([
            'data' => $p->items(),
            'meta' => [
                'current_page' => $p->currentPage(),
                'per_page' => $p->perPage(),
                'total' => $p->total(),
                'last_page' => $p->lastPage(),
            ],
        ]);
    }

    /**
     * Crea una nueva orden a partir de ítems de producto.
     */
    public function store(
        CreateSalesOrderRequest $request
    ): JsonResponse {
        $user = $this->requireStaffUser($request);

        /** @var array{client_id?: int|null, status?: string|null, items?: array<int, array<string, mixed>>} $data */
        $data = $request->validated();
        /** @var array<int, array{product_id:int, qty:int}> $itemsNormalized */
        $itemsNormalized = [];
        $itemsInput = is_array($data['items'] ?? null) ? $data['items'] : [];
        foreach ($itemsInput as $it) {
            if (! is_array($it)) {
                continue;
            }

            $pidRaw = $it['product_id'] ?? null;
            $qtyRaw = $it['qty'] ?? null;
            $pid = is_int($pidRaw) ? $pidRaw : (is_numeric($pidRaw) ? (int) $pidRaw : 0);
            $qty = is_int($qtyRaw) ? $qtyRaw : (is_numeric($qtyRaw) ? (int) $qtyRaw : 0);
            if ($pid > 0 && $qty > 0) {
                $itemsNormalized[] = [
                    'product_id' => $pid,
                    'qty' => $qty,
                ];
            }
        }

        $status = is_string($data['status'] ?? null)
            ? $data['status'] : 'requested';

        $result = DB::transaction(
            function () use ($itemsNormalized, $data, $user, $status) {
                $uidRaw = $user->getAuthIdentifier();
                $uid = is_numeric($uidRaw) ? (int) $uidRaw : 0;
                $order = new SalesOrder([
                    'client_id' => $data['client_id'] ?? null,
                    'user_id' => $uid,
                    'status' => $status,
                    'total' => 0,
                ]);
                $order->save();

                $total = 0.0;
                foreach ($itemsNormalized as $it) {
                    $pid = (int) $it['product_id'];
                    $qty = (int) $it['qty'];
                    $product = Product::query()->findOrFail($pid);
                    $price = (float) $product->price;
                    $lineTotal = $price * $qty;

                    SalesItem::query()->create([
                        'sales_order_id' => $order->id,
                        'product_id' => $pid,
                        'qty' => $qty,
                        'price' => $price,
                    ]);

                    $total += $lineTotal;
                }

                $order->total = round($total, 2);
                $order->save();

                return $order->load('items');
            }
        );

        return response()->json($result, 201);
    }

    /**
     * Entrega una orden, descontando stock y registrando movimientos.
     *
     *
     * @throws ValidationException Si el stock es insuficiente o la orden ya fue entregada.
     */
    public function deliver(
        DeliverOrderRequest $request,
        int $orderId
    ): JsonResponse {
        $user = $this->requireStaffUser($request);

        $order = SalesOrder::query()->with('items')->findOrFail($orderId);
        throw_if(
            $order->status === 'delivered',
            ValidationException::withMessages([
                'status' => ['El pedido ya fue entregado.'],
            ])
        );

        $notesRaw = $request->validated()['notes'] ?? null;
        $notes = is_string($notesRaw) ? $notesRaw : '';

        $updated = DB::transaction(
            function () use ($order, $user, $notes, $request) {
                foreach ($order->items as $item) {
                    $product = Product::query()
                        ->lockForUpdate()
                        ->findOrFail($item->product_id);

                    throw_if(
                        $product->stock < $item->qty,
                        ValidationException::withMessages([
                            'qty' => [
                                'Stock insuficiente para entregar el pedido.',
                            ],
                        ])
                    );

                    $product->stock -= $item->qty;
                    $product->save();

                    $uidRaw = $user->getAuthIdentifier();
                    $uid = is_numeric($uidRaw) ? (int) $uidRaw : 0;
                    $movement = new StockMovement([
                        'product_id' => $product->id,
                        'user_id' => $uid,
                        'type' => 'out',
                        'quantity' => (int) $item->qty,
                        'new_stock' => (int) $product->stock,
                        'notes' => $notes !== ''
                            ? $notes : ('Entrega de pedido #'.$order->id),
                        'performed_at' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => (string) (
                            $request->header('User-Agent') ?? ''
                        ),
                    ]);
                    $movement->save();
                }

                $order->status = 'delivered';
                $order->delivered_at = now();

                $uidRaw2 = $user->getAuthIdentifier();
                $order->delivered_by = is_numeric($uidRaw2) ? (int) $uidRaw2 : 0;
                $order->save();

                return $order->load('items');
            }
        );

        return response()->json($updated);
    }

    /**
     * Actualiza el estado de una orden. Permite transición a 'prepared' o 'requested'.
     *
     *
     * @throws ValidationException Si el estado solicitado es inválido.
     */
    public function update(
        Request $request,
        int $orderId
    ): JsonResponse {
        $this->requireStaffUser($request);

        /** @var array{status:string, notes?: string|null} $data */
        $data = $request->validate([
            'status' => ['required', 'string', 'in:draft,requested,prepared'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $order = SalesOrder::query()->with('items')->findOrFail($orderId);

        throw_if(
            $order->status === 'delivered',
            ValidationException::withMessages([
                'status' => ['La orden ya fue entregada y no puede cambiarse.'],
            ])
        );

        $newStatus = (string) $data['status'];

        if ($newStatus === 'prepared') {
            DB::transaction(
                function () use ($order): void {
                    $errors = [];
                    foreach ($order->items as $item) {
                        $product = Product::query()
                            ->lockForUpdate()
                            ->findOrFail($item->product_id);

                        if ((int) $product->stock < (int) $item->qty) {
                            $errors['items'] ??= [];
                            $errors['items'][] = sprintf(
                                'Stock insuficiente para %s (SKU %s): requerido %d, disponible %d',
                                (string) ($product->name
                                    ?? ('Producto #'.$product->id)),
                                (string) ($product->sku ?? ''),
                                (int) $item->qty,
                                (int) $product->stock
                            );

                            if (
                                is_string($product->sku)
                                && $product->sku !== ''
                            ) {
                                $errors['items.'.$product->sku] = [
                                    sprintf(
                                        'Stock insuficiente: requerido %d, disponible %d',
                                        (int) $item->qty,
                                        (int) $product->stock
                                    ),
                                ];
                            }
                        }
                    }

                    throw_unless($errors === [], ValidationException::withMessages($errors));
                }
            );
        }

        $order->status = $newStatus;
        $order->save();

        return response()->json($order->fresh('items'));
    }

    /**
     * Cancela una orden no entregada.
     *
     * @throws ValidationException Si la orden ya fue entregada.
     */
    public function cancel(
        Request $request,
        int $orderId
    ): JsonResponse {
        $this->requireStaffUser($request);

        $order = SalesOrder::query()->findOrFail($orderId);

        throw_if(
            $order->status === 'delivered',
            ValidationException::withMessages([
                'status' => [
                    'La orden ya fue entregada y no puede cancelarse.',
                ],
            ])
        );

        $order->status = 'draft';
        $order->save();

        return response()->json($order->fresh('items'));
    }

    /**
     * Devuelve métricas agregadas básicas de las órdenes.
     */
    public function metrics(Request $request): JsonResponse
    {
        $this->requireStaffUser($request);

        $totalOrders = (int) SalesOrder::query()->count();

        $deliveredOrders = (int) SalesOrder::query()
            ->where('status', 'delivered')
            ->count();

        $sumTotals = (float) SalesOrder::query()->sum('total');

        return response()->json([
            'total_orders' => $totalOrders,
            'delivered_orders' => $deliveredOrders,
            'sum_totals' => round($sumTotals, 2),
        ]);
    }

    /**
     * Reporte: top productos por cantidad vendida.
     */
    public function reportTopProducts(Request $request): JsonResponse
    {
        $this->requireStaffUser($request);

        $limit = (int) $request->query('limit', 10);

        $limit = max(10, min(200, $limit));

        $sku = (string) $request->query('sku', '');
        $brand = (string) $request->query('brand', '');
        $model = (string) $request->query('model', '');

        $query = SalesItem::query()
            ->selectRaw('sales_items.product_id, products.name, products.sku, SUM(sales_items.qty) as qty_sum, SUM(sales_items.qty * sales_items.price) as total_sum')
            ->join('products', 'products.id', '=', 'sales_items.product_id')
            ->groupBy('sales_items.product_id', 'products.name', 'products.sku')
            ->orderByDesc('qty_sum')
            ->limit($limit);

        if ($sku !== '') {
            $query->where('products.sku', 'like', '%'.$sku.'%');
        }

        if ($brand !== '') {
            $query->where('products.brand', 'like', '%'.$brand.'%');
        }

        if ($model !== '') {
            $query->where('products.model', 'like', '%'.$model.'%');
        }

        $rows = $query->get();

        return response()->json($rows);
    }

    /**
     * Reporte: agotamientos de stock por mes (movimientos con new_stock = 0).
     */
    public function reportStockOuts(Request $request): JsonResponse
    {
        $this->requireStaffUser($request);

        $start = $request->query('start_date');
        $end = $request->query('end_date');
        $sku = (string) $request->query('sku', '');
        $brand = (string) $request->query('brand', '');
        $model = (string) $request->query('model', '');
        $limit = (int) $request->query('limit', 50);
        $limit = max(10, min(200, $limit));

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', stock_movements.performed_at)"
            : "DATE_FORMAT(stock_movements.performed_at, '%Y-%m')";

        $query = StockMovement::query()
            ->selectRaw(
                sprintf(
                    'stock_movements.product_id, products.name, products.sku, %s as month, COUNT(*) as events',
                    $monthExpr
                )
            )
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('stock_movements.type', 'out')
            ->where('stock_movements.new_stock', 0);

        if (is_string($start) && $start !== '') {
            $query->whereDate('stock_movements.performed_at', '>=', $start);
        }

        if (is_string($end) && $end !== '') {
            $query->whereDate('stock_movements.performed_at', '<=', $end);
        }

        if ($sku !== '') {
            $query->where('products.sku', 'like', '%'.$sku.'%');
        }

        if ($brand !== '') {
            $query->where('products.brand', 'like', '%'.$brand.'%');
        }

        if ($model !== '') {
            $query->where('products.model', 'like', '%'.$model.'%');
        }

        $rows = $query
            ->groupBy(
                'stock_movements.product_id',
                'products.name',
                'products.sku',
                'month'
            )
            ->orderByDesc('month')
            ->limit($limit)
            ->get();

        return response()->json($rows);
    }
}
