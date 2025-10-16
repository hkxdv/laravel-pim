import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/utils/cn';
import { ArrowDownIcon, ArrowUpIcon, CaretSortIcon, EyeNoneIcon } from '@radix-ui/react-icons';
import type { Column } from '@tanstack/react-table';

/**
 * Encabezado de columna de tabla (DataTableColumnHeader).
 * Facilita mostrar el título de la columna y controles de ordenamiento (y menú auxiliar si aplica).
 *
 * Props típicas:
 * - column: referencia a la columna de TanStack (si corresponde).
 * - title: texto/ nodo del encabezado.
 * - className?: estilos adicionales.
 */
interface DataTableColumnHeaderProps<TData, TValue> extends React.HTMLAttributes<HTMLDivElement> {
  /** La instancia de la columna de `@tanstack/react-table`. */
  column: Column<TData, TValue>;
  /** El título que se mostrará para la cabecera de la columna. */
  title: string;
  /** Oculta el menú contextual dejando solo la acción de ordenar al click */
  hideMenu?: boolean;
  /** Pasa clases al botón-trigger cuando aplica */
  buttonClassName?: string;
}

/**
 * Un componente reutilizable para renderizar la cabecera de una columna en un `DataTable`.
 *
 * Estándar: úsalo en conjunto con TanStackDataTable y nuestras columnas.
 * Proporciona funcionalidad para ordenar (ascendente/descendente) y opcionalmente
 * ocultar la columna a través de un menú desplegable si hideMenu es false.
 * Si la columna no es ordenable, solo muestra el título.
 *
 * @template TData - El tipo de datos de la fila de la tabla.
 * @template TValue - El tipo de valor de la columna.
 * @param props - Las props para el componente de cabecera de columna.
 * @returns Un componente de cabecera de columna con controles de ordenamiento y visibilidad.
 */
export function DataTableColumnHeader<TData, TValue>({
  column,
  title,
  className,
  hideMenu = false,
  buttonClassName,
}: Readonly<DataTableColumnHeaderProps<TData, TValue>>) {
  if (!column.getCanSort()) {
    return <div className={cn(className)}>{title}</div>;
  }

  const renderSortIcon = () => {
    const sort = column.getIsSorted();
    if (sort === 'asc') {
      return <ArrowUpIcon className="ml-2 h-4 w-4" />;
    }
    if (sort === 'desc') {
      return <ArrowDownIcon className="ml-2 h-4 w-4" />;
    }
    return <CaretSortIcon className="ml-2 h-4 w-4" />;
  };

  if (hideMenu) {
    return (
      <div className={cn('flex items-center space-x-2', className)}>
        <Button
          variant="ghost"
          size="sm"
          className={cn('data-[state=open]:bg-accent -ml-3 h-8', buttonClassName)}
          onClick={() => {
            column.toggleSorting(column.getIsSorted() === 'asc');
          }}
        >
          <span>{title}</span>
          {renderSortIcon()}
        </Button>
      </div>
    );
  }

  return (
    <div className={cn('flex items-center space-x-2', className)}>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button
            variant="ghost"
            size="sm"
            className={cn('data-[state=open]:bg-accent -ml-3 h-8', buttonClassName)}
          >
            <span>{title}</span>
            {renderSortIcon()}
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start">
          <DropdownMenuItem
            onClick={() => {
              column.toggleSorting(false);
            }}
          >
            <ArrowUpIcon className="text-muted-foreground/70 mr-2 h-3.5 w-3.5" />
            Asc
          </DropdownMenuItem>
          <DropdownMenuItem
            onClick={() => {
              column.toggleSorting(true);
            }}
          >
            <ArrowDownIcon className="text-muted-foreground/70 mr-2 h-3.5 w-3.5" />
            Desc
          </DropdownMenuItem>
          <DropdownMenuSeparator />
          <DropdownMenuItem
            onClick={() => {
              column.toggleVisibility(false);
            }}
          >
            <EyeNoneIcon className="text-muted-foreground/70 mr-2 h-3.5 w-3.5" />
            Ocultar
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
