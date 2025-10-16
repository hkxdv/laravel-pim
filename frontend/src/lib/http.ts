import axios, { type AxiosError, type AxiosRequestConfig } from 'axios';
import { getCSRFToken } from './csrf';

// Aumenta la interfaz de Axios para incluir nuestra propiedad personalizada.
declare module 'axios' {
  export interface AxiosRequestConfig {
    _isRetry?: boolean;
  }
}

let isRefreshingCSRF = false;

/**
 * Configura Axios con ajustes predeterminados e interceptores para manejar las solicitudes a la API.
 * Esto incluye establecer la URL base, habilitar credenciales y gestionar tokens CSRF.
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
    (config: AxiosRequestConfig) => {
      // Se utiliza un patrón de actualización inmutable para establecer el encabezado.
      // Esto crea un nuevo objeto de encabezados, combinando los existentes (si los hay)
      // con el nuevo, lo que garantiza la seguridad de tipos.

      // Se deshabilita esta regla del linter específicamente para esta línea.
      // Los tipos de encabezado de Axios son complejos y, aunque este patrón es seguro,
      // el linter no puede verificarlo estáticamente. Esta es una excepción documentada.
      // eslint-disable-next-line @typescript-eslint/no-unsafe-assignment
      config.headers = {
        ...config.headers,
        'X-Requested-With': 'XMLHttpRequest',
      };

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
      const originalRequest = error.config;

      // Maneja la falta de coincidencia del token CSRF (estado 419).
      if (error.response?.status === 419 && !originalRequest._isRetry) {
        if (isRefreshingCSRF) {
          // Si otra solicitud ya está refrescando el token, lanza el error para evitar un bucle.
          throw error;
        }

        isRefreshingCSRF = true;
        originalRequest._isRetry = true;

        try {
          await getCSRFToken();
          // Después de obtener un nuevo token, se reintenta la solicitud original.
          return await axios(originalRequest);
        } finally {
          isRefreshingCSRF = false;
        }
      }

      // Maneja errores de No Autorizado (401).
      if (error.response?.status === 401 && !originalRequest._isRetry) {
        // Recomendado: redirigir a una página de inicio de sesión o mostrar un modal.
        // Ejemplo: window.location.href = '/login';
      }

      throw error;
    },
  );
};
