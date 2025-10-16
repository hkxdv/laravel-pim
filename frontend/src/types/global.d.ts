import type { AxiosInstance } from 'axios';
import type { route as routeFn } from 'ziggy-js';

/**
 * Extiende el objeto `global` de TypeScript para incluir la función `route` de Ziggy.
 * Esto permite que la función `route` esté disponible globalmente en toda la aplicación
 * sin necesidad de importarla en cada archivo, y con seguridad de tipos.
 */
declare global {
  /**
   * Extensión global de window para incluir la función route de Ziggy.
   * Permite usar route() en cualquier parte de la aplicación para generar URLs.
   */
  const route: typeof routeFn;

  interface Window {
    axios: AxiosInstance;
    route: typeof routeFn;
  }
}
