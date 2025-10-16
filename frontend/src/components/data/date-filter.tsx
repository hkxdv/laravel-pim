import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { cn } from '@/utils/cn';
import { CalendarIcon, ChevronRight, RefreshCw } from 'lucide-react';
import { type ChangeEvent } from 'react';

interface DateFilterProps {
  /** Valor de la fecha "desde" */
  fromDate: string;
  /** Valor de la fecha "hasta" */
  toDate: string;
  /** Función para cambiar la fecha "desde" */
  onFromDateChange: (value: string) => void;
  /** Función para cambiar la fecha "hasta" */
  onToDateChange: (value: string) => void;
  /** Función para aplicar el filtro de fechas */
  onApplyFilter: () => void;
  /** Función para resetear el filtro de fechas */
  onResetFilter: () => void;
  /** Si los controles están deshabilitados */
  disabled?: boolean;
  /** Clase CSS adicional para el contenedor */
  className?: string;
  /** Label para el campo "desde" */
  fromLabel?: string;
  /** Label para el campo "hasta" */
  toLabel?: string;
}

/**
 * Componente para filtrar por rango de fechas.
 * Incluye campos para fecha inicial y final, y botones para aplicar/resetear.
 *
 * @deprecated Estándar de filtros: para búsquedas y filtros externos a la tabla
 * utiliza el componente SearchFilters junto con tu propio panel de filtros
 * (incluyendo campos de fecha) y compón la UI con TableCardShell y DataTable.
 */
export function DateFilter({
  fromDate,
  toDate,
  onFromDateChange,
  onToDateChange,
  onApplyFilter,
  onResetFilter,
  disabled = false,
  className,
  fromLabel = 'Desde',
  toLabel = 'Hasta',
}: Readonly<DateFilterProps>) {
  const handleFromChange = (e: ChangeEvent<HTMLInputElement>) => {
    onFromDateChange(e.target.value);
  };

  const handleToChange = (e: ChangeEvent<HTMLInputElement>) => {
    onToDateChange(e.target.value);
  };

  return (
    <div className={cn('flex flex-wrap items-end gap-3', className)}>
      <div className="flex flex-col gap-1">
        <label htmlFor="date-from" className="text-sm font-medium">
          {fromLabel}
        </label>
        <div className="relative">
          <CalendarIcon className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
          <Input
            id="date-from"
            type="date"
            value={fromDate}
            onChange={handleFromChange}
            className="pl-9"
            disabled={disabled}
          />
        </div>
      </div>

      <div className="flex items-center self-center">
        <ChevronRight className="text-muted-foreground h-4 w-4" />
      </div>

      <div className="flex flex-col gap-1">
        <label htmlFor="date-to" className="text-sm font-medium">
          {toLabel}
        </label>
        <div className="relative">
          <CalendarIcon className="text-muted-foreground absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2" />
          <Input
            id="date-to"
            type="date"
            value={toDate}
            onChange={handleToChange}
            className="pl-9"
            disabled={disabled}
          />
        </div>
      </div>

      <div className="flex gap-2">
        <Button onClick={onApplyFilter} disabled={disabled}>
          Aplicar
        </Button>
        <Button
          variant="outline"
          onClick={onResetFilter}
          disabled={disabled}
          className="flex items-center gap-1"
        >
          <RefreshCw className="h-4 w-4" />
          <span>Resetear</span>
        </Button>
      </div>
    </div>
  );
}
