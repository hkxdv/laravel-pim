import TableCardShell from '@/components/data/data-table-card-shell';
import { DataTableColumnHeader } from '@/components/data/data-table-column-header';
import { TanStackDataTable } from '@/components/tanstack/tanstack-data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import { useServerTable } from '@/hooks/use-server-table';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import { ProductActionsCell } from '@/pages/modules/inventory/components/product/product-actions-cell';
import { ProductAutocomplete } from '@/pages/modules/inventory/components/product/product-autocomplete';
import type { Product } from '@/pages/modules/inventory/types/product';
import { type Paginated, type PaginatedLinks, type PaginatedMeta, type User } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData, getUserName } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, Link, usePage } from '@inertiajs/react';
import { type ColumnDef, type SortingState } from '@tanstack/react-table';
import React, { useMemo, useState } from 'react';
import { route } from 'ziggy-js';
import type { ProductListPageProps } from '../interfaces';

/**
 * Interfaz para la respuesta paginada estándar de Laravel.
 */
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

const normalizeFilters = (
  filters: unknown,
): { search?: string; sort_field?: string; sort_direction?: string; is_active?: boolean } => {
  if (!filters || Array.isArray(filters)) return {};
  const f = filters as Record<string, unknown>;
  const result: {
    search?: string;
    sort_field?: string;
    sort_direction?: string;
    is_active?: boolean;
  } = {};
  if (typeof f['search'] === 'string') result.search = f['search'];
  if (typeof f['sort_field'] === 'string') result.sort_field = f['sort_field'];
  if (typeof f['sort_direction'] === 'string') result.sort_direction = f['sort_direction'];
  if (typeof f['is_active'] === 'boolean') result.is_active = f['is_active'];
  return result;
};

type StatusFilter = 'all' | 'active' | 'inactive';

const mapStatusToIsActive = (status: StatusFilter): boolean | undefined => {
  if (status === 'all') return undefined;
  return status === 'active';
};

interface SearchModeInfo {
  label: string;
  variant: 'info' | 'gray' | 'outline';
}

const getSearchModeInfo = (raw?: string): SearchModeInfo => {
  const mode = (raw ?? '').toLowerCase();
  if (mode === 'typesense') return { label: 'Typesense', variant: 'info' };
  if (mode === 'sqlite') return { label: 'SQLite', variant: 'gray' };
  return { label: 'Desconocido', variant: 'outline' };
};

const ProductListPage: React.FC = () => {
  const {
    products: initialProducts,
    filters: rawFilters,
    auth,
    breadcrumbs,
    debug,
  } = usePage<PageProps & ProductListPageProps>().props as ProductListPageProps & {
    auth: { user: unknown };
  };
  useNavigationProgress({ delayMs: 150 });

  const user = extractUserData(auth.user as unknown as User);
  const { showError } = useToastNotifications();

  const computedBreadcrumbs =
    Array.isArray(breadcrumbs) && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.inventory.products.index', 'Productos');

  // Datos y filtros normalizados
  const normalizedProducts = normalizePaginated<Product>(initialProducts);
  const filters = normalizeFilters(rawFilters as unknown);

  const currentPage: number = normalizedProducts.meta.current_page;
  const perPage: number = normalizedProducts.meta.per_page;
  const lastPage: number = normalizedProducts.meta.last_page;
  const totalItems: number = normalizedProducts.meta.total;

  const initialSorting: SortingState = [
    {
      id: filters.sort_field ?? 'created_at',
      desc: filters.sort_direction === 'desc',
    },
  ];

  let initialStatus: StatusFilter = 'all';
  if (filters.is_active === true) {
    initialStatus = 'active';
  } else if (filters.is_active === false) {
    initialStatus = 'inactive';
  }
  const [statusFilter, setStatusFilter] = useState<StatusFilter>(initialStatus);

  // Filtros avanzados (deben declararse antes de usarlos en useServerTable)
  const [category] = useState<string | undefined>(undefined);
  const [priceMin] = useState<number | undefined>(undefined);
  const [priceMax] = useState<number | undefined>(undefined);
  const [brand] = useState<string | undefined>(undefined);
  const [model] = useState<string | undefined>(undefined);
  const [attributesInput] = useState<string>('');
  const parsedAttributes = useMemo(() => {
    const out: Record<string, unknown> = {};
    const txt = attributesInput.trim();
    if (!txt) return out;
    for (const pair of txt.split(',')) {
      const [k, v] = pair.split('=');
      const key = (k ?? '').trim();
      const val = (v ?? '').trim();
      if (!key) continue;
      out[key] = val;
    }
    return out;
  }, [attributesInput]);

  const {
    pagination,
    // sorting, // no se usa directamente en el componente
    setSorting,
    search,
    setSearch,
    isLoading,
    handleServerPaginationChange,
  } = useServerTable({
    routeName: 'internal.inventory.products.index',
    initialPageIndex: Math.max(0, currentPage - 1),
    initialPageSize: perPage,
    initialSorting,
    initialSearch: filters.search ?? '',
    debounceMs: 250,
    partialProps: ['products', 'filters'],
    buildParams: ({ pageIndex, pageSize, sorting, search }) => ({
      page: pageIndex + 1,
      per_page: pageSize,
      search,
      sort_field: sorting[0]?.id,
      sort_direction: sorting[0]?.desc ? 'desc' : 'asc',
      is_active: mapStatusToIsActive(statusFilter),
      category: category ?? undefined,
      price_min: priceMin ?? undefined,
      price_max: priceMax ?? undefined,
      brand: brand ?? undefined,
      model: model ?? undefined,
      attributes: parsedAttributes,
    }),
    extraDeps: [statusFilter, category, priceMin, priceMax, brand, model, attributesInput],
    onError: () => {
      showError('Error al cargar productos. Por favor, intenta de nuevo.');
    },
  });

  const columns: ColumnDef<Product>[] = useMemo(
    () => [
      {
        accessorKey: 'name',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nombre" />,
        cell: ({ row }) => {
          const meta = row.original.metadata ?? {};
          const imageUrl = typeof meta['image_url'] === 'string' ? meta['image_url'] : '';
          const q = (typeof search === 'string' ? search : '').trim();
          const name = row.original.name;
          const renderName = () => {
            if (!q) return <span className="font-medium">{name}</span>;
            const idx = name.toLowerCase().indexOf(q.toLowerCase());
            if (idx === -1) return <span className="font-medium">{name}</span>;
            const before = name.slice(0, idx);
            const match = name.slice(idx, idx + q.length);
            const after = name.slice(idx + q.length);
            return (
              <span className="font-medium">
                {before}
                <mark className="bg-yellow-200 text-black">{match}</mark>
                {after}
              </span>
            );
          };
          return (
            <div className="flex items-center gap-2">
              {imageUrl ? (
                <img src={imageUrl} alt="" className="size-8 rounded object-cover" />
              ) : (
                <div className="bg-muted size-8 rounded" />
              )}
              <div className="leading-tight">
                <div>{renderName()}</div>
                <div className="text-muted-foreground text-xs">
                  {(() => {
                    const q2 = (typeof search === 'string' ? search : '').trim();
                    const bm = `${row.original.brand ?? ''} ${row.original.model ?? ''}`.trim();
                    if (!q2) return <span>{bm}</span>;
                    const idx2 = bm.toLowerCase().indexOf(q2.toLowerCase());
                    if (idx2 === -1) return <span>{bm}</span>;
                    const before2 = bm.slice(0, idx2);
                    const match2 = bm.slice(idx2, idx2 + q2.length);
                    const after2 = bm.slice(idx2 + q2.length);
                    return (
                      <span>
                        {before2}
                        <mark className="bg-yellow-200 text-black">{match2}</mark>
                        {after2}
                      </span>
                    );
                  })()}
                </div>
              </div>
            </div>
          );
        },
      },
      {
        accessorKey: 'sku',
        header: ({ column }) => <DataTableColumnHeader column={column} title="SKU" />,
        cell: ({ row }) => {
          const q3 = (typeof search === 'string' ? search : '').trim();
          const sku = typeof row.original.sku === 'string' ? row.original.sku : '';
          if (!q3 || !sku) return <span className="text-muted-foreground">{sku}</span>;
          const idx3 = sku.toLowerCase().indexOf(q3.toLowerCase());
          if (idx3 === -1) return <span className="text-muted-foreground">{sku}</span>;
          const before3 = sku.slice(0, idx3);
          const match3 = sku.slice(idx3, idx3 + q3.length);
          const after3 = sku.slice(idx3 + q3.length);
          return (
            <span className="text-muted-foreground">
              {before3}
              <mark className="bg-yellow-200 text-black">{match3}</mark>
              {after3}
            </span>
          );
        },
      },
      {
        accessorKey: 'barcode',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Código de barras" />,
        cell: ({ row }) => <span className="text-muted-foreground">{row.original.barcode}</span>,
      },
      {
        accessorKey: 'price',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Precio" />,
        cell: ({ row }) => {
          const n = Number(row.original.price);
          return <span>${Number.isNaN(n) ? row.original.price : n.toFixed(2)}</span>;
        },
      },
      {
        accessorKey: 'stock',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Stock" />,
        cell: ({ row }) => {
          const stock = typeof row.original.stock === 'number' ? row.original.stock : 0;
          const ok = stock > 0;
          const label = ok ? `Disponible (${stock})` : 'Agotado';
          const variant: 'success' | 'destructive' = ok ? 'success' : 'destructive';
          return (
            <Tooltip>
              <TooltipTrigger asChild>
                <Badge variant={variant}>{label}</Badge>
              </TooltipTrigger>
              <TooltipContent>Stock: {stock}</TooltipContent>
            </Tooltip>
          );
        },
      },
      {
        accessorKey: 'is_active',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Estado" />,
        cell: ({ row }) => (
          <Badge variant={row.original.is_active ? 'default' : 'secondary'}>
            {row.original.is_active ? 'Activo' : 'Inactivo'}
          </Badge>
        ),
      },
      {
        id: 'actions',
        header: 'Acciones',
        cell: ({ row }) => <ProductActionsCell product={row.original} />,
        enableSorting: false,
      },
    ],
    [search],
  );

  const { label: prettySearchMode, variant: badgeVariant } = getSearchModeInfo(debug?.search_mode);

  return (
    <AppLayout user={user} breadcrumbs={computedBreadcrumbs} contextualNavItems={[]}>
      <Head title="Productos" />
      <ModuleDashboardLayout
        title="Productos"
        description="Gestión y control de productos"
        userName={getUserName(user)}
        actions={
          <Button asChild>
            <Link href={route('internal.inventory.products.create')}>Crear producto</Link>
          </Button>
        }
        mainContent={
          <TableCardShell
            title={<span>Listado de productos</span>}
            totalBadge={totalItems}
            rightHeaderContent={
              <div className="flex items-center gap-2">
                <Badge
                  variant={badgeVariant}
                  role="status"
                  aria-live="polite"
                  aria-label={`Modo de búsqueda: ${prettySearchMode}`}
                >
                  Modo: {prettySearchMode}
                </Badge>
                <span className="text-muted-foreground text-xs">{totalItems} resultados</span>
                <ProductAutocomplete
                  value={search}
                  onChange={setSearch}
                  onSelect={() => {
                    // Al seleccionar sugerencia, enviamos la búsqueda y reseteamos a primera página implícitamente
                  }}
                />
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => {
                    setSearch('');
                    setSorting(initialSorting);
                  }}
                >
                  Limpiar filtros
                </Button>
                <Select
                  value={statusFilter}
                  onValueChange={(v) => {
                    setStatusFilter(v as StatusFilter);
                  }}
                >
                  <SelectTrigger className="w-[180px]" aria-label="Filtrar por estado">
                    <SelectValue placeholder="Estado" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Todos</SelectItem>
                    <SelectItem value="active">Activos</SelectItem>
                    <SelectItem value="inactive">Inactivos</SelectItem>
                  </SelectContent>
                </Select>
                {/* Selector de ordenamiento intuitivo */}
                <Select
                  onValueChange={(val) => {
                    const map: Record<string, { id: string; desc: boolean }> = {
                      relevance: { id: 'relevance', desc: true },
                      price_asc: { id: 'price', desc: false },
                      price_desc: { id: 'price', desc: true },
                      rating_desc: { id: 'rating', desc: true },
                      new_desc: { id: 'created_at', desc: true },
                    };
                    const next = map[val] ?? { id: 'created_at', desc: true };
                    setSorting([{ id: next.id, desc: next.desc }]);
                  }}
                >
                  <SelectTrigger className="w-[220px]" aria-label="Ordenar resultados">
                    <SelectValue placeholder="Ordenar" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="relevance">Relevancia</SelectItem>
                    <SelectItem value="price_asc">Precio ascendente</SelectItem>
                    <SelectItem value="price_desc">Precio descendente</SelectItem>
                    <SelectItem value="rating_desc">Valoración</SelectItem>
                    <SelectItem value="new_desc">Novedades</SelectItem>
                  </SelectContent>
                </Select>
                {/* Filtros avanzados: categoría, precio, atributos, marca, modelo
                <Input
                  aria-label="Categoría"
                  placeholder="Categoría"
                  className="w-[160px]"
                  value={category ?? ''}
                  onChange={(e) => {
                    setCategory(e.target.value || undefined);
                  }}
                />
                <Input
                  type="number"
                  aria-label="Precio mínimo"
                  placeholder="Precio mínimo"
                  className="w-[140px]"
                  value={priceMin ?? ''}
                  onChange={(e) => {
                    setPriceMin(e.target.value ? Number(e.target.value) : undefined);
                  }}
                />
                <Input
                  type="number"
                  aria-label="Precio máximo"
                  placeholder="Precio máximo"
                  className="w-[140px]"
                  value={priceMax ?? ''}
                  onChange={(e) => {
                    setPriceMax(e.target.value ? Number(e.target.value) : undefined);
                  }}
                />
                <Input
                  aria-label="Marca"
                  placeholder="Marca"
                  className="w-[140px]"
                  value={brand ?? ''}
                  onChange={(e) => {
                    setBrand(e.target.value || undefined);
                  }}
                />
                <Input
                  aria-label="Modelo"
                  placeholder="Modelo"
                  className="w-[140px]"
                  value={model ?? ''}
                  onChange={(e) => {
                    setModel(e.target.value || undefined);
                  }}
                />
                <Input
                  aria-label="Atributos (color=rojo,talla=M)"
                  placeholder="Atributos (color=rojo,talla=M)"
                  className="w-[240px]"
                  value={attributesInput}
                  onChange={(e) => {
                    setAttributesInput(e.target.value);
                  }}
                /> */}
              </div>
            }
          >
            <TanStackDataTable<Product, unknown>
              columns={columns}
              data={normalizedProducts.data}
              paginated
              initialPageSize={perPage}
              initialSorting={initialSorting}
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
              noDataTitle="Sin productos"
              noDataMessage="No se encontraron productos para mostrar."
            />
            {/* Indicadores visuales de estado */}
            {isLoading && <div className="text-muted-foreground mt-2 text-sm">Buscando…</div>}
            {!isLoading && totalItems === 0 && (
              <div className="mt-2 text-sm">No se encontraron resultados.</div>
            )}
          </TableCardShell>
        }
      />
    </AppLayout>
  );
};

export default ProductListPage;
