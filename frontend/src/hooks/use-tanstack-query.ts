import {
  useMutation,
  useQuery,
  type UseMutationOptions,
  type UseQueryOptions,
} from '@tanstack/react-query';
import { useCallback, useState } from 'react';

/**
 * Hook para simplificar peticiones de lectura con TanStack Query
 * @param queryKey Clave única para la query
 * @param queryFn Función que realiza la petición
 * @param options Opciones adicionales
 * @returns Resultado de useQuery con manejo estandarizado
 */
export function useGenericQuery<TData, TError = unknown>(
  queryKey: unknown[],
  queryFn: () => Promise<TData>,
  options?: Omit<UseQueryOptions<TData, TError, TData>, 'queryKey' | 'queryFn'>,
) {
  return useQuery({
    queryKey,
    queryFn,
    ...options,
  });
}

/**
 * Hook para simplificar mutaciones con TanStack Query
 * @param mutationFn Función que realiza la mutación
 * @param options Opciones adicionales
 * @returns Resultado de useMutation con manejo estandarizado
 */
export function useGenericMutation<TVariables, TData, TError = unknown, TContext = unknown>(
  mutationFn: (variables: TVariables) => Promise<TData>,
  options?: Omit<UseMutationOptions<TData, TError, TVariables, TContext>, 'mutationFn'>,
) {
  return useMutation({
    mutationFn,
    ...options,
    onSettled: (data, error, variables, context, mutation) => {
      // Permitir comportamiento personalizado si existe
      if (options?.onSettled) {
        options.onSettled(data, error, variables, context, mutation);
      }
    },
  });
}

/**
 * Hook especializado para peticiones que necesitan paginación
 * @param baseQueryKey Clave base para la query
 * @param fetchFn Función para obtener datos paginados
 * @param initialPage Página inicial
 * @param initialPageSize Tamaño de página inicial
 * @param options Opciones adicionales
 * @returns Datos paginados y funciones de navegación
 */
export function usePaginatedQuery<TData>(
  baseQueryKey: unknown[],
  fetchFn: (
    page: number,
    pageSize: number,
  ) => Promise<{
    data: TData[];
    meta: { current_page: number; last_page: number; total: number };
  }>,
  initialPage = 1,
  initialPageSize = 10,
  options?: Omit<
    UseQueryOptions<
      { data: TData[]; meta: { current_page: number; last_page: number; total: number } },
      unknown,
      { data: TData[]; meta: { current_page: number; last_page: number; total: number } }
    >,
    'queryKey' | 'queryFn'
  >,
) {
  const [page, setPage] = useState(initialPage);
  const [pageSize, setPageSize] = useState(initialPageSize);

  const queryResult = useQuery({
    queryKey: [...baseQueryKey, page, pageSize],
    queryFn: () => fetchFn(page, pageSize),
    placeholderData: (previousData) => previousData, // Mantener datos anteriores mientras se cargan los nuevos
    ...options,
  });

  const { data } = queryResult;

  const handlePageChange = useCallback(
    (newPage: number) => {
      if (newPage >= 1 && (!data || newPage <= data.meta.last_page)) {
        setPage(newPage);
      }
    },
    [data],
  );

  const handlePageSizeChange = useCallback((newSize: number) => {
    setPageSize(newSize);
    setPage(1); // Resetear a la primera página al cambiar el tamaño
  }, []);

  return {
    ...queryResult,
    pagination: {
      page,
      pageSize,
      total: data?.meta.total ?? 0,
      lastPage: data?.meta.last_page ?? 1,
      setPage: handlePageChange,
      setPageSize: handlePageSizeChange,
    },
  };
}
