import { useCallback } from 'react';

/**
 * Hook personalizado que proporciona una función para restaurar los eventos de puntero en el `<body>`.
 *
 * @remarks
 * Este hook está diseñado para ser utilizado en el contexto de la navegación móvil.
 * A menudo, cuando se abre un menú de navegación superpuesto, se deshabilita la interacción
 * con el contenido principal aplicando `pointer-events: none` al `<body>`.
 *
 * Esta utilidad devuelve una función memoizada que elimina esa propiedad de estilo,
 * restaurando la capacidad del usuario para interactuar con la página.
 *
 * El hook es seguro para su uso en entornos de renderizado en el servidor (SSR),
 * ya que la manipulación del DOM solo ocurre en el lado del cliente.
 *
 * @returns Una función memoizada que, al ser llamada, re-habilita los eventos de puntero en el `<body>`.
 */
export function useMobileNavigation() {
  return useCallback(() => {
    // Se asegura de que el código solo se ejecute en el entorno del navegador.
    // eslint-disable-next-line unicorn/no-typeof-undefined
    if (typeof globalThis.document === 'undefined') {
      return;
    }

    // Elimina la propiedad 'pointer-events' para restaurar la interacción.
    globalThis.document.body.style.removeProperty('pointer-events');
  }, []);
}
