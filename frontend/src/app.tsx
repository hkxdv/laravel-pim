/**
 * @file Punto de entrada principal para la aplicación de React.
 *
 */
import { createInertiaApp } from '@inertiajs/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { createRoot } from 'react-dom/client';
import { Toaster } from './components/ui/sonner';
import './css/app.css';
import './css/base.css';
import { getCSRFToken } from './lib/csrf';
import { setupAxios } from './lib/http';
import { createTitle, resolvePage } from './lib/inertia';
import { ThemeProvider } from './providers/theme-provider';
import './ziggy.js';

// Crea una instancia del cliente de React Query.
const queryClient = new QueryClient();

// Inicializa la aplicación Inertia.js con la configuración del proyecto.
void createInertiaApp({
  /**
   * Define cómo se genera el título de cada página.
   * @see `createTitle` en `lib/inertia.ts`
   */
  title: createTitle,

  /**
   * Resuelve y carga dinámicamente los componentes de página de React.
   * @see `resolvePage` en `lib/inertia.ts`
   */
  resolve: async (name) => {
    // Intentar resolver la página normalmente
    try {
      const page = await resolvePage(name);
      return page;
    } catch (error) {
      // Si hay un error y no es una página de error, redirigir a la página de error genérica
      if (!name.startsWith('errors/')) {
        return resolvePage('errors/error-page');
      }
      throw error;
    }
  },

  /**
   * Función de configuración que se ejecuta una vez al iniciar la aplicación.
   * Se encarga de la configuración inicial y del renderizado del componente raíz.
   * @param el - El elemento HTML donde se montará la aplicación.
   * @param App - El componente raíz de Inertia.
   * @param props - Las propiedades iniciales de la página.
   */
  setup({ el, App, props }) {
    const root = createRoot(el);

    // Configura la instancia global de Axios con interceptores.
    setupAxios();

    // Se obtiene el token CSRF inicial antes de renderizar la aplicación.
    // Esto es crucial para que las solicitudes a la API funcionen correctamente.
    getCSRFToken()
      .then(() => {
        // Una vez que el token CSRF está listo, se renderiza la aplicación.
        root.render(
          <QueryClientProvider client={queryClient}>
            <ThemeProvider defaultTheme="light" storageKey="vite-ui-theme">
              <App {...props} />
            </ThemeProvider>
            <Toaster />
          </QueryClientProvider>,
        );
      })
      .catch((error: unknown) => {
        // En caso de que falle la obtención del token, se muestra un error.
        // Esto evita que la aplicación se cargue en un estado inconsistente.
        console.error(
          'Error crítico al inicializar la aplicación: no se pudo obtener el token CSRF.',
          error,
        );
        root.render(
          <div className="flex h-screen items-center justify-center bg-red-100 text-red-800">
            <p>Error de inicialización. Por favor, recargue la página.</p>
          </div>,
        );
      });
  },

  /**
   * Configuración de la barra de progreso que se muestra durante la navegación.
   */
  progress: {
    color: '#4B5563',
  },
});
