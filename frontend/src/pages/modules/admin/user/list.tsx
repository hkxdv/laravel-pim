import TableCardShell from '@/components/data/data-table-card-shell';
import { DataTableColumnHeader } from '@/components/data/data-table-column-header';
import { TanStackDataTable } from '@/components/tanstack/tanstack-data-table';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import { useServerTable } from '@/hooks/use-server-table';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import { UserActionsCell } from '@/pages/modules/admin/components/user/user-actions-cell';
import type { Paginated, PaginatedLinks, PaginatedMeta, StaffUser } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData } from '@/utils/user-data';
import { Head, Link, usePage } from '@inertiajs/react';
import { type ColumnDef, type SortingState } from '@tanstack/react-table';
import { CreditCard, Info, Mail, PlusCircle, User } from 'lucide-react';
import { useMemo } from 'react';
import { route } from 'ziggy-js';
import type { UserListPageProps } from '../interfaces';

/**
 * Interfaz para la respuesta paginada estándar de Laravel.
 * Laravel envía las propiedades de paginación directamente en la raíz del objeto.
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

/**
 * Crea un objeto Paginated vacío con valores por defecto seguros.
 */
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

/**
 * Normaliza la respuesta paginada de Laravel al formato esperado por el frontend.
 * Transforma la estructura plana de Laravel a la estructura anidada con meta y links.
 */
const normalizePaginated = <T,>(
  input: Paginated<T> | LaravelPaginatedResponse<T> | undefined,
): Paginated<T> => {
  // Si no hay input, retornar valores por defecto
  if (!input) {
    return makeDefaultPaginated<T>();
  }

  // Si ya tiene la estructura correcta (meta anidado), retornar tal cual
  if ('meta' in input) {
    return input;
  }

  // Si es la respuesta estándar de Laravel (propiedades planas), transformarla
  const laravelResponse = input;
  if ('current_page' in laravelResponse && Array.isArray(laravelResponse.data)) {
    const meta: PaginatedMeta = {
      current_page: laravelResponse.current_page,
      from: laravelResponse.from ?? 0,
      last_page: laravelResponse.last_page,
      links: laravelResponse.links.map((link) => ({
        url: link.url,
        label: link.label,
        active: link.active,
      })),
      path: laravelResponse.path,
      per_page: laravelResponse.per_page,
      to: laravelResponse.to ?? 0,
      total: laravelResponse.total,
    };

    const links: PaginatedLinks = {
      first: laravelResponse.first_page_url,
      last: laravelResponse.last_page_url,
      prev: laravelResponse.prev_page_url,
      next: laravelResponse.next_page_url,
    };

    return {
      data: laravelResponse.data,
      meta,
      links,
    };
  }

  // Si no coincide con ninguna estructura conocida, retornar valores por defecto
  return makeDefaultPaginated<T>();
};

/**
 * Normaliza el objeto filters para asegurar que siempre sea un objeto válido.
 * Laravel a veces envía un array vacío cuando no hay filtros.
 */
const normalizeFilters = (
  filters: unknown,
): {
  search?: string;
  role?: string;
  sort_field?: string;
  sort_direction?: string;
} => {
  // Si filters es null, undefined, o un array, retornar objeto vacío
  if (!filters || Array.isArray(filters)) {
    return {};
  }

  // Si es un objeto, asegurar que las propiedades sean strings o undefined
  const f = filters as Record<string, unknown>;
  const out: { search?: string; role?: string; sort_field?: string; sort_direction?: string } = {};
  if (typeof f['search'] === 'string') out.search = f['search'];
  if (typeof f['role'] === 'string') out.role = f['role'];
  if (typeof f['sort_field'] === 'string') out.sort_field = f['sort_field'];
  if (typeof f['sort_direction'] === 'string') out.sort_direction = f['sort_direction'];
  return out;
};

export default function UserListPage({
  users: initialUsers,
  filters: rawFilters,
  contextualNavItems,
  breadcrumbs,
  flash,
}: Readonly<UserListPageProps>) {
  const { auth } = usePage().props;
  const { showError } = useToastNotifications();

  useNavigationProgress({ delayMs: 150 });

  const userData = extractUserData(auth.user);

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

  // Normalizar datos paginados y filtros para evitar errores de tipos
  const normalizedUsers = normalizePaginated<StaffUser>(initialUsers);
  const filters = normalizeFilters(rawFilters);

  const currentPage: number = normalizedUsers.meta.current_page;
  const perPage: number = normalizedUsers.meta.per_page;
  const lastPage: number = normalizedUsers.meta.last_page;
  const totalUsers: number = normalizedUsers.meta.total;

  const initialSorting: SortingState = [
    {
      id: filters.sort_field ?? 'created_at',
      desc: filters.sort_direction === 'desc',
    },
  ];

  const {
    pagination,
    sorting,
    setSorting,
    search,
    setSearch,
    isLoading,
    handleServerPaginationChange,
  } = useServerTable({
    routeName: 'internal.admin.users.index',
    initialPageIndex: Math.max(0, currentPage - 1),
    initialPageSize: perPage,
    initialSorting,
    initialSearch: filters.search ?? '',
    partialProps: ['users', 'filters'],
    buildParams: ({ pageIndex, pageSize, sorting, search }) => ({
      page: pageIndex + 1,
      per_page: pageSize,
      search,
      sort_field: sorting[0]?.id,
      sort_direction: sorting[0]?.desc ? 'desc' : 'asc',
    }),
    onError: () => {
      showError('Error al cargar usuarios. Por favor, intenta de nuevo.');
    },
  });

  const columns: ColumnDef<StaffUser>[] = useMemo(
    () => [
      {
        accessorKey: 'name',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Nombre" />,
        cell: ({ row }) => {
          const isCurrentUser = auth.user && row.original.id === auth.user.data.id;
          return (
            <div className="flex items-center space-x-2">
              <User className="text-muted-foreground h-4 w-4" />
              <div className="flex items-center gap-2">
                <span className="max-w-[120px] truncate font-medium sm:max-w-full">
                  {row.original.name}
                </span>
                {isCurrentUser && (
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Badge variant="outline" className="text-xs" aria-label="Usuario actual">
                        Tú
                      </Badge>
                    </TooltipTrigger>
                    <TooltipContent>Tu usuario actual</TooltipContent>
                  </Tooltip>
                )}
              </div>
            </div>
          );
        },
      },
      {
        accessorKey: 'email',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Email" />,
        cell: ({ row }) => (
          <div className="flex items-center space-x-2">
            <Mail className="text-muted-foreground h-4 w-4" />
            <span className="max-w-[180px] truncate sm:max-w-[250px] md:max-w-[300px]">
              {row.original.email}
            </span>
          </div>
        ),
      },
      {
        accessorKey: 'roles',
        header: ({ column }) => <DataTableColumnHeader column={column} title="Roles" />,
        cell: ({ row }) => {
          const roles = row.original.roles;
          if (!roles || roles.length === 0) {
            return <span className="text-muted-foreground">Sin roles</span>;
          }

          const roleColorMap: Record<string, { textColor: string; tooltip: string }> = {
            ADMIN: {
              textColor: 'text-primary',
              tooltip: 'Acceso completo a todo el sistema',
            },
            DEV: {
              textColor: 'text-primary',
              tooltip: 'Acceso completo a todo el sistema',
            },
          };

          return (
            <div className="flex flex-wrap gap-1.5">
              {roles.map((role) => {
                const roleConfig = roleColorMap[role.name.toUpperCase()] ?? {
                  textColor: 'text-muted-foreground',
                  tooltip: 'Rol estándar del sistema',
                };

                return (
                  <Tooltip key={role.id}>
                    <TooltipTrigger asChild>
                      <Badge variant="outline" className={`${roleConfig.textColor} font-medium`}>
                        <span className="flex items-center gap-1">
                          <CreditCard className="h-3 w-3" />
                          {role.name}
                        </span>
                      </Badge>
                    </TooltipTrigger>
                    <TooltipContent>{roleConfig.tooltip}</TooltipContent>
                  </Tooltip>
                );
              })}
            </div>
          );
        },
        enableSorting: false,
      },
      {
        id: 'actions',
        header: ({ column }) => (
          <DataTableColumnHeader className="pr-4 text-right" column={column} title="" />
        ),
        cell: ({ row }) =>
          auth.user?.data.id ? (
            <div className="flex justify-end">
              <UserActionsCell row={row} authUserId={auth.user.data.id} />
            </div>
          ) : null,
        enableSorting: false,
        enableHiding: false,
        meta: { headerAlign: 'right', cellAlign: 'right' },
      },
    ],
    [auth.user],
  );

  const user = auth.user;

  if (!user) {
    return (
      <>
        <Head title="Error de Autenticación" />
        <div className="flex h-screen items-center justify-center">
          <p>Usuario no autenticado. Por favor, inicie sesión de nuevo.</p>
        </div>
      </>
    );
  }

  if (!auth.user) {
    return null;
  }

  // Fallback de breadcrumbs si no vienen desde el servidor
  const computedBreadcrumbs =
    breadcrumbs && breadcrumbs.length > 0
      ? breadcrumbs.map((b) => ({
          ...b,
          href:
            typeof b.href === 'string' && !b.href.startsWith('http') && b.href !== '#'
              ? route(b.href)
              : b.href,
        }))
      : createBreadcrumbs('internal.admin.users.index', 'Lista de Usuarios');

  return (
    <AppLayout
      user={userData}
      contextualNavItems={contextualNavItems ?? []}
      breadcrumbs={computedBreadcrumbs}
    >
      <Head title="Lista de Usuarios" />
      <ModuleDashboardLayout
        title="Lista de Usuarios"
        description="Añadir, editar o eliminar cuentas de usuario del sistema interno."
        userName={userData?.name ?? ''}
        showGreeting={false}
        actions={
          <Link href={route('internal.admin.users.create')}>
            <Button variant="default" className="gap-1.5">
              <PlusCircle className="h-4 w-4" />
              Nuevo Usuario
            </Button>
          </Link>
        }
        mainContent={
          <div className="w-full px-6 py-6">
            <TableCardShell
              title="Todos los usuarios"
              totalBadge={
                <>
                  <Badge variant="outline">{totalUsers} total</Badge>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <Info
                        className="text-muted-foreground h-4 w-4 cursor-help"
                        tabIndex={0}
                        aria-label="Información sobre la lista de usuarios"
                      />
                    </TooltipTrigger>
                    <TooltipContent>
                      Lista completa de usuarios con acceso al sistema
                    </TooltipContent>
                  </Tooltip>
                </>
              }
              rightHeaderContent={
                <div className="w-full sm:w-auto sm:min-w-[300px]">
                  <Input
                    type="search"
                    placeholder="Buscar por nombre o email..."
                    aria-label="Buscar usuarios por nombre o email"
                    value={search}
                    onChange={(e) => {
                      setSearch(e.target.value);
                    }}
                    className="w-full"
                  />
                </div>
              }
            >
              <TanStackDataTable<StaffUser, unknown>
                columns={columns}
                data={normalizedUsers.data}
                searchable={false}
                paginated={true}
                serverPagination={{
                  pageIndex: pagination.pageIndex,
                  pageSize: pagination.pageSize,
                  pageCount: Math.max(1, lastPage),
                  onPaginationChange: handleServerPaginationChange,
                }}
                pageSizeOptions={[10, 20, 50, 100]}
                totalItems={totalUsers}
                onSortingChange={(next) => {
                  setSorting(next);
                }}
                initialSorting={sorting}
                loading={isLoading}
                skeletonRowCount={10}
                noDataTitle="Sin usuarios"
                noDataMessage="No se encontraron usuarios para mostrar."
              />
            </TableCardShell>
          </div>
        }
        fullWidth={true}
      />
    </AppLayout>
  );
}
