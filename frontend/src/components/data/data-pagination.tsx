import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';
import type { ReactNode } from 'react';

interface PaginationProps {
  /** Número de página actual */
  currentPage: number;
  /** Número total de páginas */
  totalPages: number;
  /** Función a llamar cuando cambia la página */
  onPageChange: (page: number) => void;
  /** Si los botones de paginación están deshabilitados */
  disabled?: boolean;
  /** Opciones para el selector "por página" */
  perPageOptions?: number[];
  /** Valor actual de elementos por página */
  perPage?: number;
  /** Función para cambiar elementos por página */
  onPerPageChange?: (value: number) => void;
  /** Número total de elementos */
  totalItems?: number;
  /** Acciones a la derecha (por ejemplo, exportar) que se mostrarán junto a los controles de paginación */
  rightActions?: ReactNode;
}

/**
 * Componente de paginación (Pagination) para listas/ tablas.
 * Pensado para integrarse con control manual de paginación en servidor o cliente.
 *
 * Props típicas:
 * - pageIndex, pageSize, pageCount: control de página actual, tamaño y total de páginas.
 * - onPageChange?: callback al cambiar de página.
 * - onPageSizeChange?: callback al cambiar tamaño de página.
 *
 * Notas:
 * - En modo servidor, sincroniza los cambios con useServerTable.
 */
export function Pagination({
  currentPage,
  totalPages,
  onPageChange,
  disabled = false,
  perPageOptions,
  perPage,
  onPerPageChange,
  totalItems,
  rightActions,
}: Readonly<PaginationProps>) {
  const safeTotalPages = Math.max(1, totalPages || 0);
  const canGoPrev = !disabled && currentPage > 1;
  const canGoNext = !disabled && currentPage < safeTotalPages;

  return (
    <nav
      aria-label="Paginación"
      className="border-border mt-6 flex w-full flex-wrap items-center justify-between gap-4 border-t py-4"
    >
      <div className="flex flex-wrap items-center gap-4">
        {perPageOptions && perPage && onPerPageChange && (
          <div className="flex items-center gap-2">
            <span className="text-muted-foreground text-sm">Mostrar</span>
            <Select
              value={perPage.toString()}
              onValueChange={(value) => {
                onPerPageChange(Number.parseInt(value));
              }}
              disabled={disabled}
            >
              <SelectTrigger className="h-8 w-[80px]" aria-label="Elementos por página">
                <SelectValue placeholder={perPage} />
              </SelectTrigger>
              <SelectContent>
                {perPageOptions.map((option) => (
                  <SelectItem key={option} value={option.toString()}>
                    {option}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            <span className="text-muted-foreground text-sm">por página</span>
          </div>
        )}

        {totalItems !== undefined && (
          <span className="text-muted-foreground text-sm">
            Total: {totalItems} elemento{totalItems === 1 ? '' : 's'}
          </span>
        )}
      </div>

      <div className="flex items-center gap-2">
        <TooltipProvider delayDuration={100}>
          <div className="flex items-center gap-1">
            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => {
                    onPageChange(1);
                  }}
                  disabled={!canGoPrev}
                  aria-label="Primera página"
                  className="h-8 w-8"
                >
                  <ChevronsLeft className="h-4 w-4" />
                  <span className="sr-only">Primera página</span>
                </Button>
              </TooltipTrigger>
              <TooltipContent>Primera página</TooltipContent>
            </Tooltip>

            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => {
                    onPageChange(currentPage - 1);
                  }}
                  disabled={!canGoPrev}
                  aria-label="Página anterior"
                  className="h-8 w-8"
                >
                  <ChevronLeft className="h-4 w-4" />
                  <span className="sr-only">Página anterior</span>
                </Button>
              </TooltipTrigger>
              <TooltipContent>Página anterior</TooltipContent>
            </Tooltip>

            <span
              aria-live="polite"
              className="bg-muted text-muted-foreground mx-2 inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
            >
              Página {currentPage} de {safeTotalPages}
            </span>

            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => {
                    onPageChange(currentPage + 1);
                  }}
                  disabled={!canGoNext}
                  aria-label="Página siguiente"
                  className="h-8 w-8"
                >
                  <ChevronRight className="h-4 w-4" />
                  <span className="sr-only">Página siguiente</span>
                </Button>
              </TooltipTrigger>
              <TooltipContent>Página siguiente</TooltipContent>
            </Tooltip>

            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  variant="outline"
                  size="icon"
                  onClick={() => {
                    onPageChange(safeTotalPages);
                  }}
                  disabled={!canGoNext}
                  aria-label="Última página"
                  className="h-8 w-8"
                >
                  <ChevronsRight className="h-4 w-4" />
                  <span className="sr-only">Última página</span>
                </Button>
              </TooltipTrigger>
              <TooltipContent>Última página</TooltipContent>
            </Tooltip>
          </div>
        </TooltipProvider>

        {rightActions && <div className="ml-2 flex items-center gap-2">{rightActions}</div>}
      </div>
    </nav>
  );
}
