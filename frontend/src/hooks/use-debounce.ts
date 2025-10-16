import { useEffect, useState } from 'react';

/**
 * Hook personalizado para "debotar" (debounce) un valor.
 * Retrasa la actualización de un valor hasta que ha pasado un
 * cierto período de tiempo sin que el valor original cambie.
 * Útil para evitar ejecuciones excesivas de efectos o callbacks,
 * como en las búsquedas en tiempo real.
 *
 * @template T - El tipo de dato del valor a debotar.
 * @param value - El valor que se va a debotar.
 * @param delay - El tiempo de espera en milisegundos antes de actualizar el valor debotado.
 * @returns El valor debotado después del retraso especificado.
 */
export function useDebounce<T>(value: T, delay: number): T {
  const [debouncedValue, setDebouncedValue] = useState<T>(value);

  useEffect(() => {
    // Actualiza el valor debotado después del retraso
    const handler = setTimeout(() => {
      setDebouncedValue(value);
    }, delay);

    // Cancela el temporizador si el valor cambia (o si el delay o el componente cambian).
    // Así es como se evita que el valor debotado se actualice si el valor original
    // cambia dentro del período de retraso. El temporizador se limpia y se reinicia.
    return () => {
      clearTimeout(handler);
    };
  }, [value, delay]);

  return debouncedValue;
}
