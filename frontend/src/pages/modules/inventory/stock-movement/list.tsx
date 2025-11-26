import TableCardShell from '@/components/data/data-table-card-shell';
import { DataTableColumnHeader } from '@/components/data/data-table-column-header';
import { TanStackDataTable } from '@/components/tanstack/tanstack-data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import { useServerTable } from '@/hooks/use-server-table';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import { ProductAutocomplete } from '@/pages/modules/inventory/components/product/product-autocomplete';
import type { Product } from '@/pages/modules/inventory/types/product';
import {
  type BreadcrumbItem,
  type Paginated,
  type PaginatedLinks,
  type PaginatedMeta,
  type User,
} from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData, getUserName } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, Link, usePage } from '@inertiajs/react';
import { type ColumnDef } from '@tanstack/react-table';
import React, { useMemo, useState } from 'react';
import { route } from 'ziggy-js';

interface StockMovement {
  id: number;
  product_id: number;
  user_id: number;
  type: 'in' | 'out' | 'adjust';
  quantity: number;
  new_stock: number | null;
  notes: string | null;
  performed_at: string;
  product?: Product;
}

const MOVEMENT_TYPE_LABEL: Record<StockMovement['type'], string> = {
  in: 'Entrada',
  out: 'Salida',
  adjust: 'Ajuste',
};

interface StockMovementListPageProps {
  movements: Paginated<StockMovement> | LaravelPaginatedResponse<StockMovement>;
  filters?: { product_id?: number; per_page?: number };
}

/** Interfaz para la respuesta paginada estándar de Laravel. */
interface LaravelPaginatedResponse<T> {
  current_page: number;
  data: T[];
  first_page_url: string;
  from: number | null;
  last_page: number;
  last_page_url: string;
  links: {
    url: string | null;
    label: string;
    active: boolean;
    page?: number | null;
  }[];
  next_page_url: string | null;
  path: string;
  per_page: number;
  prev_page_url: string | null;
  to: number | null;
  total: number;
}

/** Crea objeto Paginated vacío con valores seguros */
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

/** Normaliza respuesta paginada a formato Paginated */
const normalizePaginated = <T,>(
  input: Paginated<T> | LaravelPaginatedResponse<T> | undefined,
): Paginated<T> => {
  if (!input) return makeDefaultPaginated<T>();
  if ('meta' in input) return input;
  if ('current_page' in input && Array.isArray(input.data)) {
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

/** Normaliza filtros recibidos */
const normalizeFilters = (filters: unknown): { product_id?: number } => {
  if (!filters || Array.isArray(filters)) return {};
  const f = filters as Record<string, unknown>;
  const result: { product_id?: number } = {};
  if (typeof f['product_id'] === 'number') result.product_id = f['product_id'];
  return result;
};

const StockMovementListPage: React.FC = () => {
  const {
    movements: initialMovements,
    filters: rawFilters,
    auth,
    breadcrumbs,
  } = usePage<PageProps & StockMovementListPageProps>().props as StockMovementListPageProps & {
    auth: { user: unknown };
    breadcrumbs?: BreadcrumbItem[];
  };
  useNavigationProgress({ delayMs: 150 });

  const user = extractUserData(auth.user as User);

  const computedBreadcrumbs =
    Array.isArray(breadcrumbs) && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.inventory.stock_movements.index', 'Movimientos de stock');

  const normalizedMovements = normalizePaginated<StockMovement>(initialMovements);
  const filters = normalizeFilters(rawFilters as unknown);

  const currentPage: number = normalizedMovements.meta.current_page;
  const perPage: number = normalizedMovements.meta.per_page;
  const totalItems: number = normalizedMovements.meta.total;

  // Estado local para filtro de producto por autocompletado
  const [productSearch, setProductSearch] = useState<string>('');
  const [selectedProductId, setSelectedProductId] = useState<number | undefined>(
    typeof filters.product_id === 'number' ? filters.product_id : undefined,
  );

  const { pagination, setSorting, isLoading, handleServerPaginationChange } = useServerTable({
    routeName: 'internal.inventory.stock_movements.index',
    initialPageIndex: Math.max(0, currentPage - 1),
    initialPageSize: perPage,
    initialSorting: [],
    partialProps: ['movements', 'filters'],
    buildParams: ({ pageIndex, pageSize }) => ({
      page: pageIndex + 1,
      per_page: pageSize,
      product_id: selectedProductId ?? undefined,
    }),
    extraDeps: [selectedProductId],
  });

  const columns: ColumnDef<StockMovement>[] = useMemo(
    () => [
      {
        id: 'product_name',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Producto" />,
        cell: ({ row }) => (
          <span className="font-medium">
            {row.original.product?.name ?? `ID ${row.original.product_id}`}
          </span>
        ),
      },
      {
        id: 'availability',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Disponibilidad" />,
        cell: ({ row }) => {
          const m = row.original;
          const hasProductStock = m.product && typeof m.product.stock === 'number';
          const rawStock = hasProductStock && m.product ? m.product.stock : (m.new_stock ?? 0);
          const stock = typeof rawStock === 'number' ? rawStock : 0;
          const required = m.type === 'out' ? m.quantity : 0;
          if (m.type !== 'out') return <span className="text-muted-foreground">—</span>;
          const ok = stock >= required;
          const label = ok ? 'OK' : 'Falta';
          const variant: 'success' | 'destructive' = ok ? 'success' : 'destructive';
          return (
            <Tooltip>
              <TooltipTrigger asChild>
                <Badge variant={variant}>{label}</Badge>
              </TooltipTrigger>
              <TooltipContent>
                Stock: {stock} • Requerido: {required}
              </TooltipContent>
            </Tooltip>
          );
        },
        enableSorting: false,
      },
      {
        accessorKey: 'type',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Tipo" />,
        cell: ({ row }) => {
          const t = row.original.type;
          const label = MOVEMENT_TYPE_LABEL[t];
          return <span>{label}</span>;
        },
      },
      {
        accessorKey: 'quantity',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Cantidad" />,
        cell: ({ row }) =>
          row.original.type === 'adjust' ? <span>—</span> : <span>{row.original.quantity}</span>,
      },
      {
        accessorKey: 'new_stock',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nuevo stock" />,
        cell: ({ row }) =>
          row.original.new_stock == null ? <span>—</span> : <span>{row.original.new_stock}</span>,
      },
      {
        accessorKey: 'performed_at',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Fecha/Hora" />,
        cell: ({ row }) => <span>{new Date(row.original.performed_at).toLocaleString()}</span>,
      },
      {
        accessorKey: 'notes',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Notas" />,
        cell: ({ row }) => (
          <span className="text-muted-foreground">{row.original.notes ?? '—'}</span>
        ),
      },
    ],
    [],
  );

  return (
    <AppLayout user={user} breadcrumbs={computedBreadcrumbs} contextualNavItems={[]}>
      <Head title="Movimientos de stock" />
      <ModuleDashboardLayout
        title="Movimientos de stock"
        description="Registro y auditoría de movimientos"
        userName={getUserName(user)}
        actions={
          <Link
            href={route('internal.inventory.stock_movements.create')}
            className="bg-primary text-primary-foreground hover:bg-primary/90 inline-flex h-9 items-center rounded-md border border-transparent px-4 text-sm font-medium shadow-sm"
          >
            Registrar movimiento
          </Link>
        }
        mainContent={
          <TableCardShell
            title={<span>Listado de movimientos</span>}
            totalBadge={totalItems}
            rightHeaderContent={
              <div className="flex items-center gap-2">
                <ProductAutocomplete
                  value={productSearch}
                  onChange={setProductSearch}
                  onSelect={(item) => {
                    const rawId = item.id;
                    let nextId: number | undefined;
                    if (typeof rawId === 'number') {
                      nextId = rawId;
                    } else if (typeof rawId === 'string') {
                      const parsed = Number(rawId);
                      nextId = Number.isFinite(parsed) ? parsed : undefined;
                    } else {
                      nextId = undefined;
                    }
                    setSelectedProductId(nextId);
                  }}
                  placeholder="Buscar producto…"
                />
                <Button
                  variant="outline"
                  type="button"
                  onClick={() => {
                    setSelectedProductId(undefined);
                    setProductSearch('');
                  }}
                >
                  Limpiar
                </Button>
              </div>
            }
          >
            <TanStackDataTable<StockMovement, unknown>
              columns={columns}
              data={normalizedMovements.data}
              paginated
              initialPageSize={perPage}
              searchable={false}
              loading={isLoading}
              initialSorting={[]}
              serverPagination={{
                pageIndex: pagination.pageIndex,
                pageSize: pagination.pageSize,
                pageCount: normalizedMovements.meta.last_page,
                onPaginationChange: handleServerPaginationChange,
              }}
              onSortingChange={setSorting}
            />
          </TableCardShell>
        }
      />
    </AppLayout>
  );
};

export default StockMovementListPage;
