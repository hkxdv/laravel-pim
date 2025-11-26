import TableCardShell from '@/components/data/data-table-card-shell';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import type { User } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData, getUserName } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, Link, usePage } from '@inertiajs/react';
import React from 'react';
import { route } from 'ziggy-js';

interface StockOutRow {
  product_id: number;
  name: string;
  sku: string;
  month: string;
  events: number;
}

interface StockOutsPageProps extends PageProps {
  rows?: StockOutRow[];
  filters?: { start_date?: string; end_date?: string };
  breadcrumbs?: { title: string; href: string }[];
  pageTitle?: string;
  description?: string;
}

/**
 * Vista Inertia: Reporte de agotamientos de stock por mes.
 */
const StockOutsPage: React.FC = () => {
  const {
    auth,
    breadcrumbs,
    pageTitle,
    description,
    rows = [],
    filters,
  } = usePage<PageProps & StockOutsPageProps>().props as StockOutsPageProps & {
    auth: { user: unknown };
  };
  const user = extractUserData(auth.user as unknown as User);
  const [startDate, setStartDate] = React.useState<string>(filters?.start_date ?? '');
  const [endDate, setEndDate] = React.useState<string>(filters?.end_date ?? '');
  const [sku, setSku] = React.useState<string>('');
  const [brand, setBrand] = React.useState<string>('');
  const [model, setModel] = React.useState<string>('');

  const computedBreadcrumbs =
    Array.isArray(breadcrumbs) && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.sales.reports.stock-outs', 'Agotamientos');

  return (
    <AppLayout user={user} breadcrumbs={computedBreadcrumbs} contextualNavItems={[]}>
      <Head title={pageTitle ?? 'Agotamientos'} />
      <ModuleDashboardLayout
        title={pageTitle ?? 'Agotamientos'}
        description={description ?? 'Eventos de stock en cero por mes'}
        userName={getUserName(user)}
        actions={undefined}
        mainContent={
          <TableCardShell
            title={<span>Agotamientos de stock</span>}
            totalBadge={rows.length}
            rightHeaderContent={
              <div className="flex items-center gap-2 text-sm">
                <span className="text-muted-foreground">Desde:</span>
                <Input
                  defaultValue={startDate}
                  onChange={(e) => {
                    setStartDate(e.target.value);
                  }}
                  className="w-40"
                  aria-label="Desde"
                />
                <span className="text-muted-foreground">Hasta:</span>
                <Input
                  defaultValue={endDate}
                  onChange={(e) => {
                    setEndDate(e.target.value);
                  }}
                  className="w-40"
                  aria-label="Hasta"
                />
                <Input
                  placeholder="SKU"
                  value={sku}
                  onChange={(e) => {
                    setSku(e.target.value);
                  }}
                  className="w-28"
                  aria-label="SKU"
                />
                <Input
                  placeholder="Marca"
                  value={brand}
                  onChange={(e) => {
                    setBrand(e.target.value);
                  }}
                  className="w-32"
                  aria-label="Marca"
                />
                <Input
                  placeholder="Modelo"
                  value={model}
                  onChange={(e) => {
                    setModel(e.target.value);
                  }}
                  className="w-32"
                  aria-label="Modelo"
                />
                <Button asChild variant="outline" size="sm">
                  <Link
                    href={route('internal.sales.reports.stock-outs', {
                      start_date: startDate || undefined,
                      end_date: endDate || undefined,
                      sku: sku || undefined,
                      brand: brand || undefined,
                      model: model || undefined,
                    })}
                  >
                    Aplicar
                  </Link>
                </Button>
                <Button asChild variant="ghost" size="sm">
                  <Link href={route('internal.sales.reports.stock-outs')}>Limpiar</Link>
                </Button>
              </div>
            }
          >
            <div className="divide-y rounded border">
              <div className="grid grid-cols-4 gap-2 p-2 text-xs font-medium">
                <div>Producto</div>
                <div>SKU</div>
                <div>Mes</div>
                <div className="text-right">Eventos</div>
              </div>
              {rows.map((r, idx) => (
                <div
                  key={`${r.product_id}-${r.month}-${idx}`}
                  className="grid grid-cols-4 gap-2 p-2 text-sm"
                >
                  <div className="flex items-center gap-2">
                    <Link
                      href={route('internal.inventory.products.index', { search: r.sku })}
                      className="underline"
                      title="Ver en catálogo"
                    >
                      {r.name}
                    </Link>
                    <span className="text-muted-foreground text-xs">SKU: {r.sku}</span>
                  </div>
                  <div className="flex items-center gap-2">
                    {(() => {
                      const stockVal = (r as unknown as { stock?: number }).stock ?? 0;
                      return stockVal > 0 ? (
                        <span className="rounded bg-emerald-100 px-2 py-0.5 text-emerald-700">
                          Disponible ({stockVal})
                        </span>
                      ) : (
                        <span className="rounded bg-red-100 px-2 py-0.5 text-red-700">Agotado</span>
                      );
                    })()}
                  </div>
                  <div>{r.month}</div>
                  <div className="text-right">{r.events}</div>
                </div>
              ))}
              {rows.length === 0 && (
                <div className="text-muted-foreground p-2 text-sm">Sin datos</div>
              )}
            </div>
          </TableCardShell>
        }
      />
    </AppLayout>
  );
};

export default StockOutsPage;
