import { Skeleton } from '@/components/ui/skeleton';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

/**
 * Skeleton de tabla (DataTableSkeleton).
 * Placeholder visual para estados de carga de tablas (filas y columnas simuladas).
 *
 * Props típicas:
 * - rowCount?: cantidad de filas esqueleto.
 * - columnCount?: cantidad de columnas esqueleto.
 * - dense?: modo compacto.
 */
interface DataTableSkeletonProps {
  /** Número de columnas a renderizar en el skeleton de la tabla. */
  readonly columnCount: number;
  /** Número de filas a renderizar. Por defecto es 10. */
  readonly rowCount?: number;
}

/**
 * Renderiza un skeleton para el componente DataTable.
 *
 * @param {DataTableSkeletonProps} props Las propiedades del componente.
 * @returns Un componente JSX que representa el skeleton de la tabla.
 */
export function DataTableSkeleton({ columnCount, rowCount = 10 }: DataTableSkeletonProps) {
  return (
    <div className="w-full space-y-4">
      {/* Skeleton para la barra de herramientas (filtros y botones) */}
      <div className="flex items-center justify-between">
        <Skeleton className="h-8 w-64 rounded-md" />
        <Skeleton className="h-8 w-32 rounded-md" />
      </div>

      {/* Skeleton para la tabla */}
      <div className="rounded-md border">
        <Table>
          <TableHeader>
            <TableRow>
              {Array.from({ length: columnCount }).map((_, i) => (
                <TableHead key={i}>
                  <Skeleton className="h-5 w-full rounded-md" />
                </TableHead>
              ))}
            </TableRow>
          </TableHeader>
          <TableBody>
            {Array.from({ length: rowCount }).map((_, rowIndex) => (
              <TableRow key={rowIndex}>
                {Array.from({ length: columnCount }).map((_, colIndex) => (
                  <TableCell key={colIndex}>
                    <Skeleton className="h-5 w-full rounded-md" />
                  </TableCell>
                ))}
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </div>

      {/* Skeleton para la paginación */}
      <div className="flex items-center justify-between">
        <Skeleton className="h-8 w-32 rounded-md" />
        <div className="flex items-center space-x-2">
          <Skeleton className="h-8 w-16 rounded-md" />
          <Skeleton className="h-8 w-8 rounded-md" />
          <Skeleton className="h-8 w-8 rounded-md" />
        </div>
      </div>
    </div>
  );
}
