import { useEffect, useState } from 'react';

const MOBILE_BREAKPOINT = 768;

/**
 * Hook de React que detecta si el ancho de la ventana del navegador corresponde a un dispositivo móvil.
 *
 * @remarks
 * Este hook es seguro para su uso en entornos de renderizado en el servidor (SSR),
 * ya que el estado inicial siempre es `false` y la lógica de detección solo se ejecuta en el cliente.
 *
 * Utiliza `window.matchMedia` para una detección eficiente y se actualiza automáticamente
 * si el tamaño de la ventana cambia y cruza el punto de quiebre.
 *
 * @returns `true` si la ventana se considera de tamaño móvil, de lo contrario `false`.
 */
export function useIsMobile(): boolean {
  // Inicializa el estado en `false` para un comportamiento predecible en el servidor.
  const [isMobile, setIsMobile] = useState(false);

  useEffect(() => {
    // eslint-disable-next-line unicorn/no-typeof-undefined
    if (typeof globalThis.window === 'undefined') {
      return;
    }

    const mediaQuery = globalThis.window.matchMedia(`(max-width: ${MOBILE_BREAKPOINT - 1}px)`);

    // Función para actualizar el estado basado en si la media query coincide.
    const handleResize = () => {
      setIsMobile(mediaQuery.matches);
    };

    // Añade el listener para cambios de tamaño.
    mediaQuery.addEventListener('change', handleResize);

    // Establece el estado inicial al montar el componente en el cliente.
    handleResize();

    // Limpia el listener al desmontar el componente para evitar fugas de memoria.
    return () => {
      mediaQuery.removeEventListener('change', handleResize);
    };
  }, []);

  return isMobile;
}
