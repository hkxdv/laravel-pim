/**
 * Componente de tabla avanzada basado en TanStack Table.
 * Ofrece ordenamiento, filtrado, paginación (cliente/servidor), búsqueda y skeleton de carga.
 */
import { Pagination } from '@/components/data/data-pagination';
import { DataTableSkeleton } from '@/components/data/skeletons/data-table-skeleton';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { cn } from '@/utils/cn';
import {
  flexRender,
  getCoreRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useReactTable,
  type ColumnDef,
  type ColumnFiltersState,
  type SortingState,
  type Updater,
  type VisibilityState,
} from '@tanstack/react-table';
import { ChevronDown, ChevronUp, InfoIcon, X } from 'lucide-react';
import { useEffect, useState, type ReactNode } from 'react';

/**
 * Props del componente <TanStackDataTable>.
 * Incluyen opciones para personalizar UI, paginación y callbacks para integración servidor.
 */
export interface DataTableProps<TData, TValue> {
  /** Columnas para la tabla */
  columns: ColumnDef<TData, TValue>[];
  /** Datos a mostrar */
  data: TData[];
  /** Título para la tabla */
  title?: string;
  /** Descripción o subtítulo para la tabla */
  description?: string;
  /** Título para estado vacío */
  noDataTitle?: string;
  /** Mensaje a mostrar cuando no hay datos */
  noDataMessage?: string;
  /** Si se debe mostrar la barra de búsqueda */
  searchable?: boolean;
  /** Campo en el cual realizar la búsqueda (o 'global' para buscar en todos) */
  searchField?: string;
  /** Placeholder para el campo de búsqueda */
  searchPlaceholder?: string;
  /** Si la tabla debe ser paginada */
  paginated?: boolean;
  /** Opciones para el tamaño de página */
  pageSizeOptions?: number[];
  /** Tamaño de página inicial */
  initialPageSize?: number;
  /** Altura máxima para la tabla con scroll */
  maxHeight?: string;
  /** Acciones para mostrar arriba de la tabla */
  tableActions?: ReactNode;
  /** Acciones para mostrar en el footer */
  footerActions?: ReactNode;
  /** Clases adicionales para el contenedor */
  className?: string;
  /** Si se debe usar paginación del servidor */
  serverPagination?: {
    pageIndex: number;
    pageSize: number;
    pageCount: number;
    onPaginationChange: (pageIndex: number, pageSize: number) => void;
  };
  /** Estado de carga para mostrar skeleton */
  loading?: boolean;
  /** Número de filas del skeleton (opcional) */
  skeletonRowCount?: number;
  /** Callback externo para cambios de ordenamiento (soporte servidor) */
  onSortingChange?: (sorting: SortingState) => void;
  /** Total de elementos (útil para paginación en servidor) */
  totalItems?: number;
  /** Ordenamiento inicial (útil para reflejar estado del servidor en UI) */
  initialSorting?: SortingState;
  /** Muestra iconos de ordenamiento nativos del contenedor de tabla (además del header personalizado). Por defecto desactivado para evitar duplicados. */
  showNativeSortIcon?: boolean;
  /** Mostrar divisores verticales */
  verticalDividers?: boolean;
}

/**
 * Componente de tabla de datos avanzada usando TanStack Table.
 * Incluye funcionalidades como ordenamiento, paginación y filtrado.
 */
export function TanStackDataTable<TData, TValue>({
  columns,
  data,
  title,
  description,
  noDataTitle = 'Sin resultados',
  noDataMessage = 'No hay datos disponibles',
  searchable = true,
  searchField: _searchField = 'global',
  searchPlaceholder = 'Buscar...',
  paginated = true,
  pageSizeOptions = [10, 20, 50, 100],
  initialPageSize = 10,
  maxHeight,
  tableActions,
  footerActions,
  className,
  serverPagination,
  loading = false,
  skeletonRowCount = 10,
  onSortingChange: externalOnSortingChange,
  totalItems,
  initialSorting,
  showNativeSortIcon = false,
  verticalDividers = true,
}: Readonly<DataTableProps<TData, TValue>>) {
  // Estados para manejo de la tabla
  const [sorting, setSorting] = useState<SortingState>(initialSorting ?? []);
  const [columnFilters, setColumnFilters] = useState<ColumnFiltersState>([]);
  const [columnVisibility, setColumnVisibility] = useState<VisibilityState>({});
  const [globalFilter, setGlobalFilter] = useState('');

  // Sincronizar el ordenamiento interno solo cuando cambie el ordenamiento inicial externo
  useEffect(() => {
    if (!initialSorting) return;
    const nextStr = JSON.stringify(initialSorting);
    setSorting((prev) => {
      const prevStr = JSON.stringify(prev);
      return prevStr === nextStr ? prev : initialSorting;
    });
    // Dependemos de la versión serializada para evitar re-ejecuciones innecesarias
  }, [initialSorting]);

  // Instanciar la tabla de TanStack con opciones para cliente o servidor
  const table = useReactTable({
    data,
    columns,
    getCoreRowModel: getCoreRowModel(),
    ...(serverPagination ? {} : { getPaginationRowModel: getPaginationRowModel() }),
    getSortedRowModel: getSortedRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    onGlobalFilterChange: setGlobalFilter,
    globalFilterFn: 'includesString',
    onSortingChange: (updater: Updater<SortingState>) => {
      // Compatibilidad con updater function o estado directo, con tipos seguros
      const nextSorting: SortingState =
        typeof updater === 'function'
          ? (updater as (old: SortingState) => SortingState)(sorting)
          : updater;
      setSorting(nextSorting);
      externalOnSortingChange?.(nextSorting);
    },
    onColumnFiltersChange: setColumnFilters,
    onColumnVisibilityChange: setColumnVisibility,
    state: {
      sorting,
      columnFilters,
      columnVisibility,
      globalFilter,
      ...(serverPagination
        ? {
            pagination: {
              pageIndex: serverPagination.pageIndex,
              pageSize: serverPagination.pageSize,
            },
          }
        : {}),
    },
    ...(serverPagination
      ? {
          // Habilita modo manual para interoperar con servidor
          manualPagination: true,
          manualSorting: true,
          manualFiltering: true,
          pageCount: serverPagination.pageCount,
        }
      : {
          // Estado inicial para paginación en cliente
          initialState: {
            pagination: {
              pageSize: initialPageSize,
            },
          },
        }),
  });

  // Función para manejar cambio de página con paginación del servidor
  const handleServerPaginationChange = (newPageIndex: number) => {
    if (serverPagination) {
      serverPagination.onPaginationChange(newPageIndex, serverPagination.pageSize);
    }
  };

  // Función para manejar cambio de tamaño de página con paginación del servidor
  const handleServerPageSizeChange = (newPageSize: number) => {
    if (serverPagination) {
      serverPagination.onPaginationChange(
        0, // Volver a la primera página al cambiar el tamaño
        newPageSize,
      );
    }
  };

  // Mostrar skeleton de carga si corresponde
  if (loading) {
    return (
      <div className={cn('space-y-4', className)}>
        <DataTableSkeleton columnCount={columns.length} rowCount={skeletonRowCount} />
      </div>
    );
  }

  // Prepara filas para aplicar zebra rows y bordes consistentes
  const rows = table.getRowModel().rows;

  return (
    <div className={cn('space-y-1', className)}>
      {((title ?? description ?? searchable) || tableActions) && (
        <div className="flex flex-col space-y-3">
          {/* Título y descripción */}
          {(title ?? description) && (
            <div>
              {title && <h3 className="text-lg font-semibold">{title}</h3>}
              {description && <p className="text-muted-foreground text-sm">{description}</p>}
            </div>
          )}

          {/* Barra de búsqueda y acciones */}
          <div className="flex flex-wrap items-center justify-between gap-3">
            {searchable && (
              <div className="flex flex-1 items-center gap-x-2">
                <Input
                  placeholder={searchPlaceholder}
                  value={globalFilter}
                  onChange={(e) => {
                    setGlobalFilter(e.target.value);
                  }}
                  className="max-w-sm"
                />
                {globalFilter && (
                  <Button
                    variant="ghost"
                    size="icon"
                    onClick={() => {
                      setGlobalFilter('');
                    }}
                    className="h-8 w-8"
                  >
                    <X className="h-4 w-4" />
                  </Button>
                )}
              </div>
            )}
            {tableActions && <div className="flex items-center gap-2">{tableActions}</div>}
          </div>
        </div>
      )}

      {/* Tabla */}
      <div className="rounded-md border">
        <ScrollArea style={maxHeight ? { maxHeight } : undefined}>
          <div className="overflow-x-auto">
            <Table className="border-collapse">
              <TableHeader>
                {table.getHeaderGroups().map((headerGroup) => (
                  <TableRow
                    key={headerGroup.id}
                    className="border-border bg-muted/40 hover:bg-muted/40 border-b"
                  >
                    {headerGroup.headers.map((header, index) => {
                      const meta = header.column.columnDef.meta as
                        | { headerClassName?: string; headerAlign?: 'left' | 'center' | 'right' }
                        | undefined;

                      let headerAlignClass: string | undefined;

                      if (meta?.headerAlign === 'right') {
                        headerAlignClass = 'text-right';
                      } else if (meta?.headerAlign === 'center') {
                        headerAlignClass = 'text-center';
                      }
                      return (
                        <TableHead
                          key={header.id}
                          className={cn(
                            header.column.getCanSort() ? 'cursor-pointer select-none' : '',
                            headerAlignClass,
                            meta?.headerClassName,
                            verticalDividers ? 'border-r' : '',
                            index === headerGroup.headers.length - 1 ? 'border-r-0' : '',
                          )}
                        >
                          <div className="flex items-center gap-x-1">
                            {header.isPlaceholder
                              ? null
                              : flexRender(header.column.columnDef.header, header.getContext())}
                            {showNativeSortIcon &&
                              ({
                                asc: <ChevronUp className="h-4 w-4" />,
                                desc: <ChevronDown className="h-4 w-4" />,
                              }[header.column.getIsSorted() as string] ??
                                null)}
                          </div>
                        </TableHead>
                      );
                    })}
                  </TableRow>
                ))}
              </TableHeader>
              <TableBody>
                {rows.length > 0 ? (
                  rows.map((row, rowIndex) => (
                    <TableRow
                      key={row.id}
                      data-state={row.getIsSelected() && 'selected'}
                      className={`${rowIndex % 2 === 0 ? 'bg-background' : 'bg-muted/40'} hover:bg-muted/50 ${rowIndex < rows.length - 1 ? 'border-border border-b' : ''}`}
                    >
                      {row.getVisibleCells().map((cell, index) => {
                        const meta = cell.column.columnDef.meta as
                          | { cellClassName?: string; cellAlign?: 'left' | 'center' | 'right' }
                          | undefined;

                        let cellAlignClass: string | undefined;
                        if (meta?.cellAlign === 'right') {
                          cellAlignClass = 'text-right';
                        } else if (meta?.cellAlign === 'center') {
                          cellAlignClass = 'text-center';
                        }
                        return (
                          <TableCell
                            key={cell.id}
                            className={cn(
                              cellAlignClass,
                              meta?.cellClassName,
                              verticalDividers ? 'border-r' : '',
                              index === row.getVisibleCells().length - 1 ? 'border-r-0' : '',
                            )}
                          >
                            {flexRender(cell.column.columnDef.cell, cell.getContext())}
                          </TableCell>
                        );
                      })}
                    </TableRow>
                  ))
                ) : (
                  <TableRow>
                    <TableCell colSpan={columns.length} className="h-24 text-center">
                      <div className="flex flex-col items-center justify-center py-12 text-center">
                        <div className="bg-muted rounded-full p-3">
                          <InfoIcon className="text-muted-foreground h-6 w-6" strokeWidth={1.5} />
                        </div>
                        <h3 className="mt-4 text-lg font-medium">{noDataTitle}</h3>
                        <p className="text-muted-foreground mt-2 max-w-xs text-sm">
                          {noDataMessage}
                        </p>
                      </div>
                    </TableCell>
                  </TableRow>
                )}
              </TableBody>
            </Table>
          </div>
        </ScrollArea>
      </div>

      {/* Paginación */}
      {paginated && (data.length > 0 || (serverPagination && serverPagination.pageCount > 0)) && (
        <div className="bg-card text-card-foreground border-border rounded-b-md border-x border-b px-4">
          <div className="flex flex-wrap items-center justify-between gap-y-3">
            {serverPagination ? (
              <Pagination
                currentPage={serverPagination.pageIndex + 1}
                totalPages={serverPagination.pageCount}
                onPageChange={(page) => {
                  handleServerPaginationChange(page - 1);
                }}
                perPageOptions={pageSizeOptions}
                perPage={serverPagination.pageSize}
                onPerPageChange={handleServerPageSizeChange}
                totalItems={totalItems ?? data.length}
                rightActions={footerActions}
              />
            ) : (
              <Pagination
                currentPage={table.getState().pagination.pageIndex + 1}
                totalPages={table.getPageCount()}
                onPageChange={(page) => {
                  table.setPageIndex(page - 1);
                }}
                perPageOptions={pageSizeOptions}
                perPage={table.getState().pagination.pageSize}
                onPerPageChange={(size: number) => {
                  table.setPageSize(size);
                }}
                totalItems={totalItems ?? data.length}
                rightActions={footerActions}
              />
            )}

            {/* Fallback para casos no paginados o cuando no se renderiza Pagination */}
            {footerActions && <div className="flex items-center gap-x-2">{footerActions}</div>}
          </div>
        </div>
      )}
    </div>
  );
}
