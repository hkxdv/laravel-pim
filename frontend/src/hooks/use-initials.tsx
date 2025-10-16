import { useCallback } from 'react';

/**
 * Hook personalizado que proporciona una función de utilidad para generar iniciales a partir de un nombre completo.
 *
 * @remarks
 * Este hook está optimizado con `useCallback` para evitar recreaciones innecesarias de la función.
 * La lógica interna maneja varios casos de borde:
 * - Nombres nulos, indefinidos o vacíos.
 * - Nombres que contienen solo espacios.
 * - Nombres simples (una sola palabra).
 * - Nombres compuestos (múltiples palabras).
 *
 * @returns Una función memoizada que toma un nombre completo (`string | null | undefined`) y devuelve sus iniciales como un `string`.
 */
export function useInitials() {
  /**
   * Genera las iniciales a partir de un nombre completo.
   *
   * @param fullName - El nombre completo del cual extraer las iniciales.
   * @returns Las iniciales generadas (ej. "JP") o un carácter de reemplazo ("?") si el nombre no es válido.
   */
  return useCallback((fullName?: string | null): string => {
    // Si el nombre es nulo, indefinido o vacío, devuelve un placeholder.
    if (!fullName) {
      return '?';
    }

    // Separa el nombre en palabras, eliminando espacios extra y entradas vacías.
    const names = fullName.trim().split(' ').filter(Boolean);

    // Si después de filtrar no quedan nombres (ej. el string solo contenía espacios), devuelve placeholder.
    if (names.length === 0) {
      return '?';
    }

    // Si solo hay un nombre, devuelve su primera letra.
    if (names.length === 1) {
      return names[0]?.charAt(0).toUpperCase() ?? '';
    }

    // Para nombres compuestos, toma la inicial del primer y último nombre.
    const firstInitial = names[0]?.charAt(0) ?? '';
    const lastInitial = names.at(-1)?.charAt(0);

    return `${firstInitial}${lastInitial}`.toUpperCase();
  }, []);
}
