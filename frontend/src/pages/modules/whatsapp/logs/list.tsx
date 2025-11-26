import TableCardShell from '@/components/data/data-table-card-shell';
import { DataTableColumnHeader } from '@/components/data/data-table-column-header';
import { TanStackDataTable } from '@/components/tanstack/tanstack-data-table';
import { Badge, type badgeVariants } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useServerTable } from '@/hooks/use-server-table';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import type {
  BreadcrumbItem,
  NavItemDefinition,
  Paginated,
  PaginatedLinks,
  PaginatedMeta,
} from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData } from '@/utils/user-data';
import { Head, usePage } from '@inertiajs/react';
import { type ColumnDef, type SortingState } from '@tanstack/react-table';
import type { VariantProps } from 'class-variance-authority';
import { useMemo, useState } from 'react';

interface StaffUserRef {
  id: number;
  name: string;
}

interface AgentLog {
  id: number;
  agent_name: string;
  user_id: number | null;
  module: string | null;
  action: string | null;
  status: string;
  duration_ms: number;
  ip_address: string | null;
  user_agent: string | null;
  created_at: string;
  staff_user?: StaffUserRef;
}

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
  const laravel = input;
  if ('current_page' in laravel && Array.isArray(laravel.data)) {
    const meta: PaginatedMeta = {
      current_page: laravel.current_page,
      from: laravel.from ?? 0,
      last_page: laravel.last_page,
      links: laravel.links.map((link) => ({
        url: link.url,
        label: link.label,
        active: link.active,
      })),
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
): {
  search?: string;
  module?: string;
  status?: string;
  intent?: string;
  sort_field?: string;
  sort_direction?: string;
  start_date?: string;
  end_date?: string;
} => {
  if (!filters || Array.isArray(filters)) return {};
  const f = filters as Record<string, unknown>;
  const out: {
    search?: string;
    module?: string;
    status?: string;
    intent?: string;
    sort_field?: string;
    sort_direction?: string;
    start_date?: string;
    end_date?: string;
  } = {};
  if (typeof f['search'] === 'string') out.search = f['search'];
  if (typeof f['module'] === 'string') out.module = f['module'];
  if (typeof f['status'] === 'string') out.status = f['status'];
  if (typeof f['intent'] === 'string') out.intent = f['intent'];
  if (typeof f['sort_field'] === 'string') out.sort_field = f['sort_field'];
  if (typeof f['sort_direction'] === 'string') out.sort_direction = f['sort_direction'];
  if (typeof f['start_date'] === 'string') out.start_date = f['start_date'];
  if (typeof f['end_date'] === 'string') out.end_date = f['end_date'];
  return out;
};

type BadgeVariant = VariantProps<typeof badgeVariants>['variant'];

function getStatusBadgeVariant(status: string): BadgeVariant {
  const s = status.trim().toLowerCase();
  if (s === 'success' || s === 'ok' || s === 'completed' || s === 'done') return 'success';
  if (s === 'error' || s === 'failed' || s === 'failure') return 'destructive';
  if (s === 'warning' || s === 'warn') return 'warning';
  if (s === 'pending' || s === 'queued' || s === 'in_progress' || s === 'running') return 'info';
  return 'secondary';
}

interface WhatsAppLogsListPageProps {
  logs?: Paginated<AgentLog> | LaravelPaginatedResponse<AgentLog>;
  filters?: unknown;
  contextualNavItems?: NavItemDefinition[];
  breadcrumbs?: BreadcrumbItem[];
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
  pageTitle?: string;
  description?: string | null;
}

export default function WhatsAppLogsListPage({
  logs: initialLogs,
  filters: rawFilters,
  contextualNavItems,
  breadcrumbs,
  flash,
  pageTitle,
  description,
}: Readonly<WhatsAppLogsListPageProps>) {
  const { auth } = usePage().props;

  useFlashToasts(
    flash
      ? {
          success: flash.success ?? '',
          error: flash.error ?? '',
          info: flash.info ?? '',
          warning: flash.warning ?? '',
        }
      : undefined,
  );

  const userData = extractUserData(auth.user);

  const normalizedLogs = normalizePaginated<AgentLog>(initialLogs as Paginated<AgentLog>);
  const filters = normalizeFilters(rawFilters);

  const currentPage: number = normalizedLogs.meta.current_page;
  const perPage: number = normalizedLogs.meta.per_page;
  const lastPage: number = normalizedLogs.meta.last_page;
  const totalLogs: number = normalizedLogs.meta.total;

  const initialSorting: SortingState = [
    {
      id: filters.sort_field ?? 'created_at',
      desc: filters.sort_direction === 'desc',
    },
  ];

  const [moduleFilter, setModuleFilter] = useState<string>(filters.module ?? '');
  const [statusFilter, setStatusFilter] = useState<string>(filters.status ?? '');
  const [intentFilter, setIntentFilter] = useState<string>(filters.intent ?? '');
  const [startDateFilter, setStartDateFilter] = useState<string>(filters.start_date ?? '');
  const [endDateFilter, setEndDateFilter] = useState<string>(filters.end_date ?? '');

  const {
    pagination,
    sorting,
    setSorting,
    search,
    setSearch,
    isLoading,
    handleServerPaginationChange,
  } = useServerTable({
    routeName: 'internal.whatsapp.logs.index',
    initialPageIndex: Math.max(0, currentPage - 1),
    initialPageSize: perPage,
    initialSorting,
    initialSearch: filters.search ?? '',
    partialProps: ['logs', 'filters'],
    buildParams: ({ pageIndex, pageSize, sorting, search }) => ({
      page: pageIndex + 1,
      per_page: pageSize,
      search,
      sort_field: sorting[0]?.id,
      sort_direction: sorting[0]?.desc ? 'desc' : 'asc',
      module: moduleFilter || undefined,
      status: statusFilter || undefined,
      intent: intentFilter || undefined,
      start_date: startDateFilter || undefined,
      end_date: endDateFilter || undefined,
    }),
    extraDeps: [moduleFilter, statusFilter, intentFilter, startDateFilter, endDateFilter],
  });

  const columns: ColumnDef<AgentLog>[] = useMemo(
    () => [
      {
        accessorKey: 'id',
        header: ({ column }) => <DataTableColumnHeader column={column} title="ID" />,
        cell: ({ row }) => <span className="font-mono">{row.original.id}</span>,
        enableSorting: true,
      },
      {
        accessorKey: 'created_at',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Fecha" />,
        cell: ({ row }) => new Date(row.original.created_at).toLocaleString(),
        enableSorting: true,
      },
      {
        accessorKey: 'agent_name',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Agente" />,
        cell: ({ row }) => row.original.agent_name,
        enableSorting: true,
      },
      {
        accessorKey: 'module',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Módulo" />,
        cell: ({ row }) => row.original.module ?? '-',
        enableSorting: true,
      },
      {
        accessorKey: 'action',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Acción" />,
        cell: ({ row }) => row.original.action ?? '-',
      },
      {
        accessorKey: 'status',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Estado" />,
        cell: ({ row }) => {
          const variant = getStatusBadgeVariant(row.original.status);
          return <Badge variant={variant}>{row.original.status}</Badge>;
        },
        enableSorting: true,
      },
      {
        accessorKey: 'duration_ms',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Duración (ms)" />,
        cell: ({ row }) => row.original.duration_ms,
        enableSorting: true,
      },
      {
        accessorKey: 'ip_address',
        header: ({ column }) => <DataTableColumnHeader column={column} title="IP" />,
        cell: ({ row }) => row.original.ip_address ?? '-',
      },
      {
        accessorKey: 'user_agent',
        header: ({ column }) => <DataTableColumnHeader column={column} title="User-Agent" />,
        cell: ({ row }) => (
          <span
            className="inline-block max-w-[240px] truncate"
            title={row.original.user_agent ?? ''}
          >
            {row.original.user_agent ?? '-'}
          </span>
        ),
      },
    ],
    [],
  );

  const computedBreadcrumbs =
    breadcrumbs && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.whatsapp.logs.index', 'Logs del agente');

  return (
    <AppLayout
      user={userData}
      contextualNavItems={contextualNavItems ?? []}
      breadcrumbs={computedBreadcrumbs}
    >
      <Head title="Logs del Agente" />
      <ModuleDashboardLayout
        title={pageTitle ?? 'Logs del Agente'}
        description={description ?? ''}
        userName={userData?.name ?? ''}
        showGreeting={false}
        actions={
          <div className="flex flex-wrap items-center gap-2">
            <Input
              type="search"
              placeholder="Buscar por agente, acción, etc."
              aria-label="Buscar logs por agente, acción, etc."
              value={search}
              onChange={(e) => {
                setSearch(e.target.value);
              }}
              className="w-full sm:w-[240px]"
            />
            <Input
              placeholder="Módulo"
              aria-label="Filtrar por módulo"
              value={moduleFilter}
              onChange={(e) => {
                setModuleFilter(e.target.value);
              }}
              className="w-full sm:w-[200px]"
            />
            <Input
              placeholder="Estado"
              aria-label="Filtrar por estado"
              value={statusFilter}
              onChange={(e) => {
                setStatusFilter(e.target.value);
              }}
              className="w-full sm:w-[200px]"
            />
            <Input
              placeholder="Intent"
              aria-label="Filtrar por intent"
              value={intentFilter}
              onChange={(e) => {
                setIntentFilter(e.target.value);
              }}
              className="w-full sm:w-[200px]"
            />
            <Input
              type="date"
              placeholder="Inicio"
              aria-label="Fecha inicio"
              value={startDateFilter}
              onChange={(e) => {
                setStartDateFilter(e.target.value);
              }}
              className="w-full sm:w-[180px]"
            />
            <Input
              type="date"
              placeholder="Fin"
              aria-label="Fecha fin"
              value={endDateFilter}
              onChange={(e) => {
                setEndDateFilter(e.target.value);
              }}
              className="w-full sm:w-[180px]"
            />
            <Button
              variant="secondary"
              onClick={() => {
                setSearch('');
                setModuleFilter('');
                setStatusFilter('');
                setIntentFilter('');
                setStartDateFilter('');
                setEndDateFilter('');
              }}
            >
              Limpiar
            </Button>
          </div>
        }
        mainContent={
          <div className="w-full px-6 py-6">
            <TableCardShell title="Todos los logs">
              <TanStackDataTable<AgentLog, unknown>
                columns={columns}
                data={normalizedLogs.data}
                searchable={false}
                paginated={true}
                serverPagination={{
                  pageIndex: pagination.pageIndex,
                  pageSize: pagination.pageSize,
                  pageCount: Math.max(1, lastPage),
                  onPaginationChange: handleServerPaginationChange,
                }}
                pageSizeOptions={[10, 20, 50, 100]}
                totalItems={totalLogs}
                onSortingChange={(next) => {
                  setSorting(next);
                }}
                initialSorting={sorting}
                loading={isLoading}
                skeletonRowCount={10}
                noDataTitle="Sin logs"
                noDataMessage="No se encontraron logs para mostrar."
              />
            </TableCardShell>
          </div>
        }
        fullWidth={true}
      />
    </AppLayout>
  );
}
