import { format, isValid as isValidDate, parseISO } from 'date-fns';

// Helpers de formato para normalizar Fecha y Hora
export const formatDateYMD = (value?: string | null) => {
  if (!value) return '-';
  try {
    const d1 = parseISO(value);
    if (isValidDate(d1)) return format(d1, 'yyyy-MM-dd');
    // Fallback: si viene con tiempo, cortar a YYYY-MM-DD
    const match = /^(\d{4}-\d{2}-\d{2})/.exec(value);
    if (match) return match[1];
    const d2 = new Date(value);
    if (!Number.isNaN(d2.getTime())) return format(d2, 'yyyy-MM-dd');
  } catch {
    /* noop */
  }
  return value;
};

export const formatTimeHM = (value?: string | null) => {
  if (!value) return '-';
  // HH:mm:ss -> HH:mm
  const hms = /^(\d{2}):(\d{2}):(\d{2})/.exec(value);
  if (hms) return `${hms[1]}:${hms[2]}`;
  // ISO o similar
  try {
    if (value.includes('T')) {
      const d = parseISO(value);
      if (isValidDate(d)) return format(d, 'HH:mm');
    }
  } catch {
    /* noop */
  }
  // Ya viene en HH:mm
  return value;
};
