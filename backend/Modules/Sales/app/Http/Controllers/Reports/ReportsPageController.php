<?php

declare(strict_types=1);

namespace Modules\Sales\App\Http\Controllers\Reports;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Response as InertiaResponse;
use Modules\Inventory\App\Models\StockMovement;
use Modules\Sales\App\Http\Controllers\SalesBaseController;
use Modules\Sales\App\Models\SalesItem;

final class ReportsPageController extends SalesBaseController
{
    /**
     * Reporte: top productos por cantidad vendida.
     */
    public function topProducts(Request $request): InertiaResponse
    {
        $limit = (int) $request->integer('limit', 10);
        $rows = SalesItem::query()
            ->selectRaw(
                'sales_items.product_id, products.name, products.sku, products.stock, SUM(sales_items.qty) as qty_sum, SUM(sales_items.qty * sales_items.price) as total_sum'
            )
            ->join('products', 'products.id', '=', 'sales_items.product_id')
            ->groupBy('sales_items.product_id', 'products.name', 'products.sku')
            ->orderByDesc('qty_sum')
            ->limit($limit)
            ->get();

        return $this->prepareAndRenderModuleView(
            view: 'reports/top-products',
            request: $request,
            additionalData: [
                'rows' => $rows,
                'filters' => [
                    'limit' => $limit,
                ],
            ],
            routeSuffix: 'reports.top-products'
        );
    }

    /**
     * Reporte: agotamientos de stock por mes.
     */
    public function stockOuts(Request $request): InertiaResponse
    {
        $start = (string) $request->string('start_date')->toString();
        $end = (string) $request->string('end_date')->toString();

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite'
            ? "strftime('%Y-%m', stock_movements.performed_at)"
            : "DATE_FORMAT(stock_movements.performed_at, '%Y-%m')";

        $query = StockMovement::query()
            ->selectRaw(sprintf(
                'stock_movements.product_id, products.name, products.sku, products.stock, %s as month, COUNT(*) as events',
                $monthExpr
            ))
            ->join('products', 'products.id', '=', 'stock_movements.product_id')
            ->where('stock_movements.type', 'out')
            ->where('stock_movements.new_stock', 0);

        if ($start !== '') {
            $query->whereDate('stock_movements.performed_at', '>=', $start);
        }

        if ($end !== '') {
            $query->whereDate('stock_movements.performed_at', '<=', $end);
        }

        $rows = $query
            ->groupBy(
                'stock_movements.product_id',
                'products.name',
                'products.sku',
                'month'
            )
            ->orderByDesc('month')
            ->limit(50)
            ->get();

        return $this->prepareAndRenderModuleView(
            view: 'reports/stock-outs',
            request: $request,
            additionalData: [
                'rows' => $rows,
                'filters' => [
                    'start_date' => $start !== '' ? $start : null,
                    'end_date' => $end !== '' ? $end : null,
                ],
            ],
            routeSuffix: 'reports.stock-outs'
        );
    }
}
