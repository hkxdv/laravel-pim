import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/utils/cn';
import { Filter, Search, X } from 'lucide-react';
import { type ReactNode } from 'react';

interface SearchFiltersProps {
  /** Valor actual del término de búsqueda */
  searchValue?: string;
  /** Función a llamar cuando cambia el término de búsqueda */
  onSearchChange?: (value: string) => void;
  /** Placeholder para el campo de búsqueda */
  searchPlaceholder?: string;
  /** Si mostrar el campo de búsqueda */
  showSearch?: boolean;
  /** Si los filtros están expandidos o colapsados */
  filtersExpanded?: boolean;
  /** Función para alternar la expansión de los filtros */
  onToggleFilters?: () => void;
  /** Contenido del panel de filtros */
  filterContent?: ReactNode;
  /** Botón de acción principal (ej: Aplicar Filtros) */
  actionButton?: ReactNode;
  /** Acciones adicionales a la derecha */
  additionalActions?: ReactNode;
  /** Acciones alineadas a la derecha en la fila superior (junto al buscador) */
  rightActions?: ReactNode;
  /** Clase CSS adicional para el contenedor */
  className?: string;
}

/**
 * Buscador y filtros rápidos (SearchFilters) para listas/ tablas.
 * Renderiza input de búsqueda y, opcionalmente, chips/ selectores de filtros.
 *
 * Props típicas:
 * - value?: término de búsqueda.
 * - onChange?: callback para cambios de búsqueda.
 * - filters?: definición de filtros adicionales.
 */
export function SearchFilters({
  searchValue = '',
  onSearchChange,
  searchPlaceholder = 'Buscar...',
  showSearch = true,
  filtersExpanded = false,
  onToggleFilters,
  filterContent,
  actionButton,
  additionalActions,
  rightActions,
  className,
}: Readonly<SearchFiltersProps>) {
  return (
    <div className={cn('space-y-4', className)}>
      <div className="flex flex-wrap items-center gap-2">
        {showSearch && (
          <div className="relative flex-grow">
            <Search className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
            <Input
              type="text"
              placeholder={searchPlaceholder}
              value={searchValue}
              onChange={(e) => onSearchChange?.(e.target.value)}
              className="pl-9"
            />
            {searchValue && (
              <Button
                variant="ghost"
                size="sm"
                className="absolute top-0 right-0 h-full rounded-l-none px-3"
                onClick={() => onSearchChange?.('')}
              >
                <X className="text-muted-foreground h-4 w-4" />
              </Button>
            )}
          </div>
        )}

        {filterContent && onToggleFilters && (
          <Button
            variant="outline"
            size="sm"
            onClick={onToggleFilters}
            className={cn('flex items-center gap-2', filtersExpanded && 'bg-muted')}
          >
            <Filter className="h-4 w-4" />
            {filtersExpanded ? 'Ocultar filtros' : 'Mostrar filtros'}
          </Button>
        )}

        {additionalActions}
        {rightActions && <div className="ml-auto flex items-center gap-2">{rightActions}</div>}
      </div>

      {filtersExpanded && filterContent && (
        <div className="bg-card rounded-md border p-4 shadow-sm">{filterContent}</div>
      )}

      {actionButton && <div className="flex justify-end">{actionButton}</div>}
    </div>
  );
}
