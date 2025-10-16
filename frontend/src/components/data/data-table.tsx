import { TanStackDataTable } from '@/components/tanstack/tanstack-data-table';
import type { PaginatedResponse } from '@/types';
import type { ColumnDef, SortingState } from '@tanstack/react-table';

/**
 * Tabla reutilizable (DataTable) para escenarios de datos en cliente o compuestos.
 * Si los datos provienen del servidor con paginación/ordenamiento, preferir TanStackDataTable + useServerTable.
 *
 * Props típicas:
 * - columns, data: definición de columnas y filas.
 * - searchable?, filters?, pagination?: herramientas de UI integradas.
 *
 * Notas:
 * - Mantener este componente para casos client-side o layouts particulares.
 * - Para flujo consolidado de servidor, usar el stack recomendado.
 */

interface DataTableProps<TData, TValue> {
  columns: ColumnDef<TData, TValue>[];
  data: PaginatedResponse<TData> | undefined;
  onPageChange: (page: number) => void;
  onPageSizeChange?: (pageSize: number) => void;
  onSortingChange?: (sorting: SortingState) => void;
  search?: {
    term: string;
    onClear: () => void;
  };
  loading?: boolean;
  skeletonRowCount?: number;
  /** Mostrar los iconos nativos de sort de TanStack en los headers */
  showNativeSortIcon?: boolean;
  /** Mostrar divisores verticales entre columnas para igualar la UI unificada */
  verticalDividers?: boolean;
  /** Título para el estado vacío (se muestra junto al ícono) */
  noDataTitle?: string;
}

export function DataTable<TData, TValue>({
  columns,
  data,
  onPageChange,
  onPageSizeChange,
  onSortingChange,
  search,
  loading = false,
  skeletonRowCount,
  showNativeSortIcon,
  verticalDividers,
  noDataTitle,
}: Readonly<DataTableProps<TData, TValue>>) {
  const pagination = data
    ? {
        pageIndex: data.current_page - 1,
        pageSize: data.per_page,
        pageCount: data.last_page,
        onPaginationChange: (pageIndex: number, pageSize: number) => {
          if (pageSize && pageSize !== data.per_page) {
            onPageSizeChange?.(pageSize);
          }
          onPageChange(pageIndex + 1);
        },
      }
    : undefined;

  const noDataMessage = search?.term
    ? `No se encontraron resultados para "${search.term}"`
    : 'No hay datos disponibles.';

  return (
    <div className="w-full overflow-hidden">
      <TanStackDataTable
        columns={columns}
        data={data?.data ?? []}
        {...(pagination ? { serverPagination: pagination } : {})}
        searchable={false} // La búsqueda se maneja externamente
        paginated={!!pagination}
        noDataMessage={noDataMessage}
        noDataTitle={noDataTitle ?? ''}
        loading={loading}
        {...(skeletonRowCount === undefined ? {} : { skeletonRowCount })}
        {...(search?.term
          ? {
              footerActions: (
                <button
                  type="button"
                  onClick={search.onClear}
                  className="text-primary text-sm hover:underline"
                >
                  Limpiar búsqueda y mostrar todo
                </button>
              ),
            }
          : {})}
        {...(onSortingChange ? { onSortingChange } : {})}
        {...(data?.total === undefined ? {} : { totalItems: data.total })}
        {...(showNativeSortIcon === undefined ? {} : { showNativeSortIcon })}
        {...(verticalDividers === undefined ? {} : { verticalDividers })}
      />
    </div>
  );
}
