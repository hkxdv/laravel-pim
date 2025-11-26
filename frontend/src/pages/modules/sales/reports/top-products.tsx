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

interface TopProductRow {
  product_id: number;
  name: string;
  sku: string;
  qty_sum: number;
  total_sum: number;
  stock?: number;
}

interface TopProductsPageProps extends PageProps {
  rows?: TopProductRow[];
  filters?: { limit?: number };
  breadcrumbs?: { title: string; href: string }[];
  pageTitle?: string;
  description?: string;
}

/**
 * Vista Inertia: Reporte de top productos por cantidad vendida.
 */
const TopProductsPage: React.FC = () => {
  const {
    auth,
    breadcrumbs,
    pageTitle,
    description,
    rows = [],
    filters,
  } = usePage<PageProps & TopProductsPageProps>().props as TopProductsPageProps & {
    auth: { user: unknown };
  };
  const user = extractUserData(auth.user as unknown as User);
  const [limit, setLimit] = React.useState<number>(() => {
    const l = filters?.limit;
    return typeof l === 'number' ? l : 10;
  });
  const [sku, setSku] = React.useState<string>('');
  const [brand, setBrand] = React.useState<string>('');
  const [model, setModel] = React.useState<string>('');

  const computedBreadcrumbs =
    Array.isArray(breadcrumbs) && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.sales.reports.top-products', 'Top productos');

  return (
    <AppLayout user={user} breadcrumbs={computedBreadcrumbs} contextualNavItems={[]}>
      <Head title={pageTitle ?? 'Top productos'} />
      <ModuleDashboardLayout
        title={pageTitle ?? 'Top productos'}
        description={description ?? 'Ranking por cantidad vendida'}
        userName={getUserName(user)}
        actions={undefined}
        mainContent={
          <TableCardShell
            title={<span>Ranking de productos</span>}
            totalBadge={rows.length}
            rightHeaderContent={
              <div className="flex items-center gap-2 text-sm">
                <span className="text-muted-foreground">Límite:</span>
                <Input
                  defaultValue={limit}
                  onChange={(e) => {
                    const v = Number(e.target.value);
                    setLimit(Number.isNaN(v) ? 10 : v);
                  }}
                  className="w-20"
                  aria-label="Límite"
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
                    href={route('internal.sales.reports.top-products', {
                      limit,
                      sku: sku || undefined,
                      brand: brand || undefined,
                      model: model || undefined,
                    })}
                  >
                    Aplicar
                  </Link>
                </Button>
                <Button asChild variant="ghost" size="sm">
                  <Link href={route('internal.sales.reports.top-products')}>Limpiar</Link>
                </Button>
              </div>
            }
          >
            <div className="divide-y rounded border">
              <div className="grid grid-cols-4 gap-2 p-2 text-xs font-medium">
                <div>Producto</div>
                <div>SKU</div>
                <div className="text-right">Cantidad</div>
                <div className="text-right">Total</div>
              </div>
              {rows.map((r) => (
                <div key={r.product_id} className="grid grid-cols-4 gap-2 p-2 text-sm">
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
                    {(r.stock ?? 0) > 0 ? (
                      <span className="rounded bg-emerald-100 px-2 py-0.5 text-emerald-700">
                        Disponible ({r.stock ?? 0})
                      </span>
                    ) : (
                      <span className="rounded bg-red-100 px-2 py-0.5 text-red-700">Agotado</span>
                    )}
                  </div>
                  <div className="text-right">{r.qty_sum}</div>
                  <div className="text-right">${r.total_sum.toFixed(2)}</div>
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

export default TopProductsPage;
