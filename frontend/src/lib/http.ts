import axios, {
  AxiosHeaders,
  type AxiosError,
  type AxiosRequestConfig,
  type InternalAxiosRequestConfig,
} from 'axios';
import { getCSRFToken } from './csrf';

/**
 * Conjunto de peticiones que ya han sido reintentadas.
 * Se utiliza para prevenir bucles de reintento infinitos.
 *
 * @internal
 */
const retriedRequests = new WeakSet<object>();

/**
 * Bandera que indica si en este momento se está refrescando el token CSRF.
 *
 * @internal
 */
let isRefreshingCSRF = false;

/**
 * Configura los valores por defecto globales de Axios y registra interceptores
 * de solicitud y respuesta.
 *
 * Esta función **debe** ejecutarse exactamente una vez antes de que se emita la
 * primera petición HTTP. El lugar habitual para hacerlo es el punto de entrada
 * de la aplicación, tal como se muestra en `src/app.tsx`:
 *
 * ```ts
 * // src/app.tsx
 * import { setupAxios } from './lib/http';
 *
 * setupAxios();
 * ```
 *
 * Funcionalidades principales:
 *
 * 1. Establece `axios.defaults.baseURL` usando la variable de entorno
 *    `VITE_APP_URL` (con *fallback* a `http://localhost:8080`).
 * 2. Habilita `axios.defaults.withCredentials` para que las cookies (p.e. las
 *    emitidas por Laravel Sanctum) se envíen automáticamente.
 * 3. Registra un interceptor **de solicitud** que garantiza la presencia del
 *    encabezado `X-Requested-With: XMLHttpRequest` en *todas* las peticiones.
 * 4. Registra un interceptor **de respuesta** que maneja de forma automática
 *    los siguientes códigos de error:
 *    - **419 – CSRF Token Mismatch**: obtiene un nuevo token ejecutando
 *      {@link getCSRFToken} y reintenta la petición original *una única vez*.
 *    - **401 – Unauthorized**: expone un único punto en el que se puede
 *      redirigir al usuario a la pantalla de inicio de sesión o mostrar un
 *      cuadro de diálogo.
 *
 * La lógica de reintento se protege con un {@link WeakSet} de peticiones ya
 * reintentadas para evitar bucles infinitos.
 *
 * @throws {AxiosError} Propaga cualquier error de Axios que no pueda ser
 *         gestionado de forma transparente por los interceptores.
 *
 * @public
 */
export const setupAxios = (): void => {
  // Establece la URL base para todas las solicitudes de Axios desde las variables de entorno de Vite.
  axios.defaults.baseURL = import.meta.env.VITE_APP_URL || 'http://localhost:8080';

  // Habilita el envío de cookies con solicitudes entre sitios. Esencial para Sanctum.
  axios.defaults.withCredentials = true;

  // --- Interceptor de Solicitudes de Axios ---
  // Se añade el encabezado 'X-Requested-With' a cada solicitud saliente.
  // Este es un enfoque más robusto y seguro que modificar los valores por defecto globales.
  axios.interceptors.request.use(
    (config: InternalAxiosRequestConfig) => {
      // Se utiliza un patrón de actualización inmutable para establecer el encabezado.
      // Esto crea un nuevo objeto de encabezados, combinando los existentes (si los hay)
      // con el nuevo, lo que garantiza la seguridad de tipos.

      // Se deshabilita esta regla del linter específicamente para esta línea.
      // Los tipos de encabezado de Axios son complejos y, aunque este patrón es seguro,
      // el linter no puede verificarlo estáticamente. Esta es una excepción documentada.

      const existing = config.headers;

      if (existing instanceof AxiosHeaders) {
        existing.set('X-Requested-With', 'XMLHttpRequest');
        config.headers = existing;
      } else {
        const headers = new AxiosHeaders(existing);
        headers.set('X-Requested-With', 'XMLHttpRequest');
        config.headers = headers;
      }

      return config;
    },
    (error: unknown) => {
      // Se asegura que la razón del rechazo de la promesa sea siempre un objeto Error,
      // como lo requieren las buenas prácticas y las reglas del linter.
      if (error instanceof Error) {
        return Promise.reject(error);
      }
      return Promise.reject(
        new Error('Ocurrió un error inesperado durante la configuración de la solicitud.', {
          cause: error,
        }),
      );
    },
  );

  // --- Interceptor de Respuestas de Axios ---
  // Este interceptor maneja las respuestas y los errores de forma global.
  axios.interceptors.response.use(
    (response) => response, // Devuelve directamente las respuestas exitosas.
    async (error: AxiosError) => {
      const originalRequest: InternalAxiosRequestConfig | undefined = error.config;

      // Si no hay request original, no se puede reintentar
      if (!originalRequest) {
        throw error;
      }

      // Maneja la falta de coincidencia del token CSRF (estado 419).
      if (error.response?.status === 419 && !retriedRequests.has(originalRequest)) {
        if (isRefreshingCSRF) {
          // Si otra solicitud ya está refrescando el token, lanza el error para evitar un bucle.
          throw error;
        }

        isRefreshingCSRF = true;
        retriedRequests.add(originalRequest);

        try {
          await getCSRFToken();
          // Después de obtener un nuevo token, se reintenta la solicitud original.
          return await axios.request(originalRequest as AxiosRequestConfig);
        } finally {
          isRefreshingCSRF = false;
        }
      }

      // Maneja errores de No Autorizado (401).
      if (error.response?.status === 401 && !retriedRequests.has(originalRequest)) {
        // Recomendado: redirigir a una página de inicio de sesión o mostrar un modal.
        // Ejemplo: window.location.href = '/login';
      }

      throw error;
    },
  );
};
