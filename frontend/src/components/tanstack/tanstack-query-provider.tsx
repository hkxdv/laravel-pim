import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { type ReactNode, useState } from 'react';

/**
 * Opciones para configurar el QueryClient
 */
interface TanStackQueryProviderProps {
  /** Elementos hijos a renderizar dentro del provider */
  children: ReactNode;
  /** Opciones adicionales de configuración */
  options?: {
    /** Tiempo predeterminado de refresco en milisegundos */
    defaultRefetchInterval?: number;
    /** Si se debe mostrar errores en consola */
    logErrors?: boolean;
    /** Si se debe recargar al perder el foco */
    refetchOnWindowFocus?: boolean;
  };
}

/**
 * Componente proveedor para TanStack Query.
 * Configura un cliente de consulta para ser usado en toda la aplicación.
 */
export function TanStackQueryProvider({
  children,
  options = {},
}: Readonly<TanStackQueryProviderProps>) {
  const {
    defaultRefetchInterval = 0, // Por defecto no refrescar automáticamente
    logErrors = true,
    refetchOnWindowFocus = false,
  } = options;

  // Crear una instancia del cliente por componente para evitar
  // problemas de hidratación en SSR
  const [queryClient] = useState(
    () =>
      new QueryClient({
        defaultOptions: {
          queries: {
            // Configuración global para todas las consultas
            refetchInterval: defaultRefetchInterval,
            refetchOnWindowFocus,
            retry: 1,
            staleTime: 5 * 60 * 1000, // 5 minutos
            gcTime: 10 * 60 * 1000, // 10 minutos
          },
          mutations: {
            // Determinar si se debe registrar errores en la consola
            ...(logErrors && {
              onError: (error: Error) => {
                console.error('Error de mutación:', error);
              },
            }),
          },
        },
      }),
  );

  return <QueryClientProvider client={queryClient}>{children}</QueryClientProvider>;
}
