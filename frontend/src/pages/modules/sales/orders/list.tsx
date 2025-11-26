import TableCardShell from '@/components/data/data-table-card-shell';
import { DataTableColumnHeader } from '@/components/data/data-table-column-header';
import { TanStackDataTable } from '@/components/tanstack/tanstack-data-table';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import { useServerTable } from '@/hooks/use-server-table';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import type { Paginated, PaginatedLinks, PaginatedMeta, User } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData, getUserName } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, Link, usePage } from '@inertiajs/react';
import type { ColumnDef, SortingState } from '@tanstack/react-table';
import React from 'react';
import { route } from 'ziggy-js';
import type { Order, OrdersListPageProps } from '../interfaces';

interface LaravelPaginatedResponse<T> {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: { url: string | null; label: string; active: boolean; page?: number | null }[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

const isLaravelResponse = <T,>(input: unknown): input is LaravelPaginatedResponse<T> => {
  if (!input || typeof input !== 'object') return false;
  const anyInput = input as Record<string, unknown>;
  return 'current_page' in anyInput && Array.isArray(anyInput['data']);
};

const isRecord = (obj: unknown): obj is Record<string, unknown> => {
  return !!obj && typeof obj === 'object';
};

const makeDefaultPaginated = <T,>(): Paginated<T> => {
  const meta: PaginatedMeta = {
    current_page: 1,
    from: 0,
    last_page: 1,
    links: [],
    path: '',
    per_page: 10,
    to: 0,
    total: 0,
  };
  const links: PaginatedLinks = { first: '', last: '', prev: null, next: null };
  return { data: [], meta, links };
};

const normalizePaginated = <T,>(
  input: Paginated<T> | LaravelPaginatedResponse<T> | undefined,
): Paginated<T> => {
  if (!input) return makeDefaultPaginated<T>();
  if ('meta' in input) return input;
  if (isLaravelResponse<T>(input)) {
    const laravel = input;
    const meta: PaginatedMeta = {
      current_page: laravel.current_page,
      from: laravel.from ?? 0,
      last_page: laravel.last_page,
      links: laravel.links.map((l) => ({ url: l.url, label: l.label, active: l.active })),
      path: laravel.path,
      per_page: laravel.per_page,
      to: laravel.to ?? 0,
      total: laravel.total,
    };
    const links: PaginatedLinks = {
      first: laravel.first_page_url,
      last: laravel.last_page_url,
      prev: laravel.prev_page_url,
      next: laravel.next_page_url,
    };
    return { data: laravel.data, meta, links };
  }
  return makeDefaultPaginated<T>();
};

/**
 * Página Inertia que muestra el listado de órdenes del Módulo de Ventas.
 *
 * Consume props `orders` y `filters` entregadas por el backend y
 * normaliza la respuesta paginada para la tabla.
 *
 * Ejemplo de uso: ruta `internal.sales.orders.list`.
 */
const OrdersListPage: React.FC = () => {
  const {
    orders: initialOrders,
    auth,
    breadcrumbs,
    description,
    pageTitle,
    filters: rawFilters,
  } = usePage<PageProps & OrdersListPageProps>().props as OrdersListPageProps & {
    auth: { user: unknown };
  };

  useNavigationProgress({ delayMs: 150 });
  const user = extractUserData(auth.user as unknown as User);
  const { showError } = useToastNotifications();

  const computedBreadcrumbs =
    Array.isArray(breadcrumbs) && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.sales.orders.list', 'Órdenes');

  const normalized = normalizePaginated<Order>(initialOrders);

  const currentPage: number = normalized.meta.current_page;
  const perPage: number = normalized.meta.per_page;
  const lastPage: number = normalized.meta.last_page;
  const totalItems: number = normalized.meta.total;

  let filtersObj: Record<string, unknown> = {};
  if (isRecord(rawFilters)) {
    filtersObj = rawFilters;
  }
  const [status, setStatus] = React.useState<string>(() => {
    const s = filtersObj['status'];
    return typeof s === 'string' ? s : 'all';
  });

  const initialSorting: SortingState = [
    {
      id: 'created_at',
      desc: filtersObj['sort_direction'] === 'asc' ? false : true,
    },
  ];

  const { pagination, setSorting, isLoading, handleServerPaginationChange, sorting } =
    useServerTable({
      routeName: 'internal.sales.orders.list',
      initialPageIndex: Math.max(0, currentPage - 1),
      initialPageSize: perPage,
      initialSorting,
      debounceMs: 250,
      partialProps: ['orders', 'filters'],
      buildParams: ({ pageIndex, pageSize, sorting }) => {
        const firstSort: { id: string; desc: boolean } =
          sorting.length > 0 && sorting[0] && typeof sorting[0].id === 'string'
            ? { id: sorting[0].id, desc: sorting[0].desc }
            : { id: 'created_at', desc: true };
        return {
          page: pageIndex + 1,
          per_page: pageSize,
          sort_field: firstSort.id,
          sort_direction: firstSort.desc ? 'desc' : 'asc',
          status: status === 'all' ? undefined : status,
        };
      },
      extraDeps: [status],
      onError: () => {
        showError('Error al cargar órdenes. Por favor, intenta de nuevo.');
      },
    });

  const columns: ColumnDef<Order>[] = [
    {
      accessorKey: 'id',
      header: ({ column }) => <DataTableColumnHeader column={column} title="Orden" />,
      cell: ({ row }) => (
        <Link
          href={route('internal.sales.orders.detail', { order: row.original.id })}
          className="font-medium"
        >
          #{row.original.id}
        </Link>
      ),
    },
    {
      id: 'availability',
      header: ({ column }) => <DataTableColumnHeader column={column} title="Disponibilidad" />,
      cell: ({ row }) => {
        const items = row.original.items ?? [];
        if (!Array.isArray(items) || items.length === 0)
          return <span className="text-muted-foreground">-</span>;
        let ok = true;
        const shortages: { sku: string; required: number; stock: number }[] = [];
        for (const it of items) {
          const stock = it.product?.stock ?? 0;
          const required = it.qty;
          if (stock < required) {
            ok = false;
            shortages.push({
              sku: typeof it.product?.sku === 'string' ? it.product.sku : '',
              required,
              stock,
            });
          }
        }
        const label = ok ? 'OK' : 'Falta';
        const variant: 'success' | 'destructive' = ok ? 'success' : 'destructive';
        const content = ok
          ? 'Todos los ítems cumplen stock'
          : shortages
              .map((s) => `${s.sku || 'SKU'}: req ${s.required} / stk ${s.stock}`)
              .join(' \u2022 ');
        return (
          <Tooltip>
            <TooltipTrigger asChild>
              <Badge variant={variant}>{label}</Badge>
            </TooltipTrigger>
            <TooltipContent>{content}</TooltipContent>
          </Tooltip>
        );
      },
      enableSorting: false,
    },
    {
      accessorKey: 'status',
      header: ({ column }) => <DataTableColumnHeader column={column} title="Estado" />,
      cell: ({ row }) => {
        const labelMap = {
          draft: 'Borrador',
          requested: 'Solicitado',
          prepared: 'Preparado',
          delivered: 'Entregado',
        } as const;
        const variantMap = {
          draft: 'outline',
          requested: 'secondary',
          prepared: 'secondary',
          delivered: 'default',
        } as const;
        const s = row.original.status;
        return <Badge variant={variantMap[s]}>{labelMap[s]}</Badge>;
      },
    },
    {
      accessorKey: 'total',
      header: ({ column }) => <DataTableColumnHeader column={column} title="Total" />,
      cell: ({ row }) => {
        const n = Number(row.original.total);
        return <span>${Number.isNaN(n) ? row.original.total : n.toFixed(2)}</span>;
      },
    },
    {
      accessorKey: 'items_count',
      header: ({ column }) => <DataTableColumnHeader column={column} title="Ítems" />,
      cell: ({ row }) => <span>{row.original.items_count ?? 0}</span>,
    },
    {
      accessorKey: 'created_at',
      header: ({ column }) => <DataTableColumnHeader column={column} title="Creada" />,
      cell: ({ row }) => (
        <span className="text-muted-foreground text-sm">{row.original.created_at ?? ''}</span>
      ),
    },
    {
      accessorKey: 'delivered_at',
      header: ({ column }) => <DataTableColumnHeader column={column} title="Entregada" />,
      cell: ({ row }) => (
        <span className="text-muted-foreground text-sm">{row.original.delivered_at ?? ''}</span>
      ),
    },
  ];

  return (
    <AppLayout user={user} breadcrumbs={computedBreadcrumbs} contextualNavItems={[]}>
      <Head title={pageTitle ?? 'Órdenes'} />
      <ModuleDashboardLayout
        title={pageTitle ?? 'Órdenes'}
        description={description ?? 'Gestión de órdenes y estados'}
        userName={getUserName(user)}
        actions={undefined}
        mainContent={
          <TableCardShell
            title={<span>Listado de órdenes</span>}
            totalBadge={totalItems}
            rightHeaderContent={
              <div className="flex items-center gap-2">
                <span className="text-muted-foreground text-xs">{totalItems} resultados</span>
                <select
                  aria-label="Estado"
                  className="rounded border px-2 py-1 text-sm"
                  value={status}
                  onChange={(e) => {
                    setStatus(e.target.value);
                  }}
                >
                  <option value="all">Todos</option>
                  <option value="requested">Solicitado</option>
                  <option value="prepared">Preparado</option>
                  <option value="delivered">Entregado</option>
                </select>
                <button
                  className="text-xs underline"
                  onClick={() => {
                    setStatus('all');
                    setSorting(initialSorting);
                  }}
                >
                  Limpiar filtros
                </button>
              </div>
            }
          >
            <TanStackDataTable<Order, unknown>
              columns={columns}
              data={normalized.data}
              paginated
              initialPageSize={perPage}
              initialSorting={sorting}
              loading={isLoading}
              serverPagination={{
                pageIndex: pagination.pageIndex,
                pageSize: pagination.pageSize,
                pageCount: lastPage,
                onPaginationChange: handleServerPaginationChange,
              }}
              onSortingChange={setSorting}
              totalItems={totalItems}
              showNativeSortIcon={false}
              className="mt-2"
              searchable={false}
              skeletonRowCount={10}
              pageSizeOptions={[10, 20, 50, 100]}
              noDataTitle="Sin órdenes"
              noDataMessage="No se encontraron órdenes para mostrar."
            />
            {isLoading && <div className="text-muted-foreground mt-2 text-sm">Cargando…</div>}
            {!isLoading && totalItems === 0 && (
              <div className="mt-2 text-sm">No se encontraron resultados.</div>
            )}
          </TableCardShell>
        }
      />
    </AppLayout>
  );
};

export default OrdersListPage;
