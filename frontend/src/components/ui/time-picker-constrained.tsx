'use client';

import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { cn } from '@/utils/cn';
import { format, setHours, setMinutes } from 'date-fns';
import { Clock } from 'lucide-react';
import * as React from 'react';

/**
 * Selector de hora con rango configurable.
 *
 * - Permite elegir una hora y minutos dentro de un rango [min, max].
 * - Muestra solo horas/minutos válidos acorde a los límites establecidos.
 * - Emite el valor en formato "HH:mm" (24h).
 * - Basado en componentes shadcn/ui (Button, Popover, Select).
 
 */
/**
 * Props del componente para definir valor, rango y granularidad.
 * - min/max: límites inclusivos en formato "HH:mm".
 * - minuteStep: define la granularidad de minutos (ej. 60, 30, 15, 5).
 */
interface TimePickerConstrainedProps {
  value?: string; // Formato "HH:mm"
  onChange: (value: string) => void;
  disabled?: boolean;
  /** Hora mínima permitida en HH:mm (inclusiva). Por defecto: "09:00" */
  min?: string;
  /** Hora máxima permitida en HH:mm (inclusiva). Por defecto: "16:00" */
  max?: string;
  /** Paso de minutos. Ejemplo: 60 -> solo ":00"; 15 -> cuartos de hora. Por defecto: 5 */
  minuteStep?: number;
}

/**
 * Convierte una cadena HH:mm a minutos totales desde 00:00.
 * Si el valor es inválido/undefined, retorna el valor de respaldo.
 */
function parseTimeToMinutes(hhmm: string | undefined, fallbackMinutes: number): number {
  if (!hhmm) return fallbackMinutes;
  const [h, m] = hhmm.split(':').map((n) => Number.parseInt(n, 10));
  const hours = Number.isFinite(h) ? h : 0;
  const minutes = Number.isFinite(m) ? m : 0;
  return (hours ?? 0) * 60 + (minutes ?? 0);
}

/**
 * Convierte minutos totales a cadena HH:mm (24h).
 * Si el valor es inválido/undefined, retorna el valor de respaldo.
 */
function minutesToHHmm(total: number | undefined, fallbackHHmm: string): string {
  if (!total) return fallbackHHmm;
  const h = Math.floor(total / 60);
  const m = total % 60;
  return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
}

/**
 * Componente principal que renderiza el selector de hora con restricciones.
 *
 * - Sincroniza estado interno con la prop `value`.
 * - Calcula dinámicamente las horas/minutos disponibles según `min`, `max` y `minuteStep`.
 */
export function TimePickerConstrained({
  value,
  onChange,
  disabled,
  min = '09:00',
  max = '16:00',
  minuteStep = 5,
}: Readonly<TimePickerConstrainedProps>) {
  const minTotal = React.useMemo(() => parseTimeToMinutes(min, 0), [min]);
  const maxTotal = React.useMemo(() => parseTimeToMinutes(max, 23 * 60 + 59), [max]);

  const [date, setDate] = React.useState(() => {
    const initialTotal = parseTimeToMinutes(value, minTotal);
    const clamped = Math.min(Math.max(initialTotal, minTotal), maxTotal);
    const d = new Date();
    return setMinutes(setHours(d, Math.floor(clamped / 60)), clamped % 60);
  });

  // Mantiene sincronía con cambios externos en `value`
  React.useEffect(() => {
    if (!value) return;
    const total = parseTimeToMinutes(value, minTotal);
    const clamped = Math.min(Math.max(total, minTotal), maxTotal);
    const d = setMinutes(setHours(new Date(), Math.floor(clamped / 60)), clamped % 60);
    setDate(d);
  }, [value, minTotal, maxTotal]);

  // Cálculos de límites (hora/minuto) para el rango
  const minHour = Math.floor(minTotal / 60);
  const minMinute = minTotal % 60;
  const maxHour = Math.floor(maxTotal / 60);
  const maxMinute = maxTotal % 60;

  // Lista de horas disponibles en el rango
  const hours = React.useMemo(() => {
    const list: string[] = [];
    for (let h = minHour; h <= maxHour; h += 1) {
      list.push(String(h).padStart(2, '0'));
    }
    return list;
  }, [minHour, maxHour]);

  // Hora seleccionada actualmente (para filtrar minutos válidos)
  const selectedHour = Number.parseInt(format(date, 'HH'), 10);

  // Lista de minutos válidos para la hora seleccionada, respetando minuteStep
  const minutesForHour = React.useMemo(() => {
    let start = 0;
    let end = 59;
    if (selectedHour === minHour) start = minMinute;
    if (selectedHour === maxHour) end = maxMinute;
    const list: string[] = [];
    // Alinear inicio al paso
    let mStart = start;
    if (mStart % minuteStep !== 0) mStart += minuteStep - (mStart % minuteStep);
    for (let m = mStart; m <= end; m += minuteStep) {
      list.push(String(m).padStart(2, '0'));
    }
    return list.length > 0 ? list : [String(start).padStart(2, '0')];
  }, [selectedHour, minHour, minMinute, maxHour, maxMinute, minuteStep]);

  // Maneja cambios de hora, ajustando minutos a los límites/step válidos
  const handleHourChange = (hour: string) => {
    const h = Number.parseInt(hour, 10);
    let m = Number.parseInt(format(date, 'mm'), 10);
    // Calcular ventana de minutos válida para la hora elegida
    let start = 0;
    let end = 59;
    if (h === minHour) start = minMinute;
    if (h === maxHour) end = maxMinute;
    // Alinear al paso y limitar al rango
    if (m % minuteStep !== 0) m = m - (m % minuteStep);
    if (m < start) m = start - (start % minuteStep);
    if (m < start || m % minuteStep !== 0) {
      m = start % minuteStep === 0 ? start : start + (minuteStep - (start % minuteStep));
    }
    if (m > end) m = end - (end % minuteStep);

    const total = h * 60 + m;
    const clamped = Math.min(Math.max(total, minTotal), maxTotal);

    const newDate = setMinutes(setHours(date, Math.floor(clamped / 60)), clamped % 60);
    setDate(newDate);
    onChange(format(newDate, 'HH:mm'));
  };

  // Maneja cambios de minuto, clampéa a rango y emite HH:mm
  const handleMinuteChange = (minute: string) => {
    const m = Number.parseInt(minute, 10);
    let h = Number.parseInt(format(date, 'HH'), 10);
    if (h === minHour && m < minMinute) h = minHour;
    if (h === maxHour && m > maxMinute) h = maxHour;

    const total = h * 60 + m;
    const clamped = Math.min(Math.max(total, minTotal), maxTotal);

    const newDate = setMinutes(setHours(date, Math.floor(clamped / 60)), clamped % 60);
    setDate(newDate);
    onChange(format(newDate, 'HH:mm'));
  };

  // Valor mostrado siempre clampéado para evitar inconsistencias de UI
  const displayValue = React.useMemo(() => {
    const total =
      Number.parseInt(format(date, 'HH'), 10) * 60 + Number.parseInt(format(date, 'mm'), 10);
    const clamped = Math.min(Math.max(total, minTotal), maxTotal);
    return minutesToHHmm(clamped, '00:00');
  }, [date, minTotal, maxTotal]);

  return (
    // Popover con dos Select: horas y minutos
    // y un botón disparador con icono de reloj
    <Popover>
      <PopoverTrigger asChild>
        <Button
          variant="outline"
          className={cn(
            'w-full justify-start text-left font-normal',
            !value && 'text-muted-foreground',
            'focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px]',
            'aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive border-b-accent-foreground/50',
          )}
          disabled={disabled}
        >
          <Clock className="mr-2 h-4 w-4" />
          {value ? <span>{displayValue}</span> : <span>Selecciona una hora</span>}
        </Button>
      </PopoverTrigger>
      <PopoverContent className="w-auto p-2">
        <div className="flex items-center gap-2">
          <Select
            value={format(date, 'HH')}
            onValueChange={handleHourChange}
            disabled={disabled ?? false}
          >
            <SelectTrigger className="w-[80px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent className="max-h-60">
              {hours.map((hour) => (
                <SelectItem key={hour} value={hour}>
                  {hour}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
          <span>:</span>
          <Select
            value={format(date, 'mm')}
            onValueChange={handleMinuteChange}
            disabled={disabled ?? false}
          >
            <SelectTrigger className="w-[80px]">
              <SelectValue />
            </SelectTrigger>
            <SelectContent className="max-h-60">
              {minutesForHour.map((minute) => (
                <SelectItem key={minute} value={minute}>
                  {minute}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
        <div className="text-muted-foreground mt-2 text-xs">
          Disponible de {min} a {max}
        </div>
      </PopoverContent>
    </Popover>
  );
}
