/**
 * Hook reutilizable para tablas con paginación/ordenamiento/búsqueda en servidor usando Inertia.
 *
 * Expone estado y handlers para integrarse con TanStack Table, centralizando navegación y parámetros.
 */
import { useDebounce } from '@/hooks/use-debounce';
import { type RequestPayload, router } from '@inertiajs/core';
import type { PaginationState, SortingState } from '@tanstack/react-table';
import { startTransition, useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { route } from 'ziggy-js';

export interface UseServerTableOptions {
  /** Nombre de la ruta Ziggy, por ejemplo: 'internal.admin.users.index' */
  routeName: string;
  /** Parámetros de ruta requeridos por Ziggy (segmentos), si aplica */
  routeParams?: Record<string, unknown>;
  /** Índice 0-based de página inicial proveniente del servidor */
  initialPageIndex: number;
  /** Tamaño de página inicial proveniente del servidor */
  initialPageSize: number;
  /** Número de páginas total (solo para UI). Mantener actualizado desde props del servidor. */
  pageCount?: number;
  /** Ordenamiento inicial proveniente de filtros o valores por defecto */
  initialSorting?: SortingState;
  /** Término de búsqueda inicial proveniente de filtros */
  initialSearch?: string;
  /** Milisegundos de debounce para la búsqueda */
  debounceMs?: number;
  /**
   * Lista de props de Inertia que deseas refrescar en recargas parciales (opción `only`).
   * Si no se provee, Inertia devolverá todas las props como de costumbre.
   */
  partialProps?: string[];
  /**
   * Función que mapea el estado interno hacia los parámetros esperados por el backend
   * (p. ej. { page, per_page, search, sort_field, sort_direction }).
   */
  buildParams: (args: {
    pageIndex: number;
    pageSize: number;
    sorting: SortingState;
    search: string;
  }) => Record<string, unknown>;
  /** Callback ejecutado si ocurre un error al navegar */
  onError?: (err: unknown) => void;
  /** Dependencias externas (p. ej. filtros) que deben disparar la navegación al cambiar */
  extraDeps?: unknown[];
}

export interface UseServerTableResult {
  /** Estado de paginación (pageIndex 0-based y pageSize) */
  pagination: PaginationState;
  /** Setter para actualizar la paginación manualmente */
  setPagination: (next: PaginationState) => void;
  /** Estado de ordenamiento TanStack */
  sorting: SortingState;
  /** Setter para actualizar el ordenamiento */
  setSorting: (next: SortingState) => void;
  /** Término de búsqueda crudo (no debounced) */
  search: string;
  /** Setter para actualizar el término de búsqueda */
  setSearch: (next: string) => void;
  /** Flag de carga mientras se realiza la navegación Inertia */
  isLoading: boolean;
  /** Helper compatible con TanStackDataTable serverPagination.onPaginationChange */
  handleServerPaginationChange: (pageIndex: number, pageSize: number) => void;
}

export function useServerTable({
  routeName,
  routeParams,
  initialPageIndex,
  initialPageSize,
  initialSorting = [],
  initialSearch = '',
  debounceMs = 300,
  partialProps,
  buildParams,
  onError,
  extraDeps = [],
}: Readonly<UseServerTableOptions>): UseServerTableResult {
  // Estado local de paginación con valores iniciales provenientes del servidor
  const [pagination, setPagination] = useState<PaginationState>({
    pageIndex: Math.max(0, initialPageIndex),
    pageSize: initialPageSize,
  });
  // Estado de ordenamiento de TanStack Table
  const [sorting, setSorting] = useState<SortingState>(initialSorting);
  // Estado del término de búsqueda
  const [search, setSearch] = useState(initialSearch);
  // Búsqueda con debounce para evitar navegación excesiva
  const debouncedSearch = useDebounce(search, debounceMs);
  // Flag de carga mientras se resuelve la navegación Inertia
  const [isLoading, setIsLoading] = useState(false);
  // Ref para aplicar initialSorting solo una vez (en primer render)
  const hasInitializedSortingRef = useRef(false);
  // Recordar últimos valores para detectar cambios relevantes que requieren reset de página
  const lastSearchRef = useRef<string>(initialSearch);
  const lastSortingRef = useRef<string>(JSON.stringify(initialSorting));

  // Setters envueltos en transición para reducir bloqueo en handlers de click
  const setSortingTransition = useCallback((next: SortingState) => {
    startTransition(() => {
      setSorting(next);
    });
  }, []);

  const setSearchTransition = useCallback((next: string) => {
    startTransition(() => {
      setSearch(next);
    });
  }, []);

  const setPaginationTransition = useCallback((next: PaginationState) => {
    startTransition(() => {
      setPagination(next);
    });
  }, []);

  // Mantener sincronizado el estado local cuando cambian los valores iniciales desde el servidor
  useEffect(() => {
    const next = { pageIndex: Math.max(0, initialPageIndex), pageSize: initialPageSize };
    setPagination((prev) => {
      if (prev.pageIndex !== next.pageIndex || prev.pageSize !== next.pageSize) return next;
      return prev;
    });
  }, [initialPageIndex, initialPageSize]);

  // Serialización estable del ordenamiento inicial para dependencias del efecto
  const initialSortingStr = useMemo(() => JSON.stringify(initialSorting), [initialSorting]);
  // Versión parseada estable para aplicar cuando cambie el ordenamiento inicial
  const initialSortingParsed = useMemo(
    () => JSON.parse(initialSortingStr) as SortingState,
    [initialSortingStr],
  );

  // Memo estable para los parámetros de ruta (evita re-navegaciones por identidad de objeto)
  const routeParamsStr = useMemo(() => JSON.stringify(routeParams ?? {}), [routeParams]);
  const routeParamsMemo = useMemo(
    () => JSON.parse(routeParamsStr) as Record<string, unknown>,
    [routeParamsStr],
  );
  const routeUrl = useMemo(() => route(routeName, routeParamsMemo), [routeName, routeParamsMemo]);

  // Sincronizar ordenamiento cuando cambia la entrada inicial (solo una vez)
  useEffect(() => {
    if (hasInitializedSortingRef.current) return;
    setSorting(initialSortingParsed);
    hasInitializedSortingRef.current = true;
  }, [initialSortingStr, initialSortingParsed]);

  // Cuando cambia el término de búsqueda (debounced), reseteamos a la primera página
  useEffect(() => {
    if (lastSearchRef.current !== debouncedSearch) {
      setPagination((prev) => (prev.pageIndex === 0 ? prev : { ...prev, pageIndex: 0 }));
      lastSearchRef.current = debouncedSearch;
    }
  }, [debouncedSearch]);

  // Cuando cambia el ordenamiento (después del inicial), reseteamos a la primera página
  useEffect(() => {
    if (!hasInitializedSortingRef.current) return;
    const currentSortingStr = JSON.stringify(sorting);
    if (lastSortingRef.current !== currentSortingStr) {
      setPagination((prev) => (prev.pageIndex === 0 ? prev : { ...prev, pageIndex: 0 }));
      lastSortingRef.current = currentSortingStr;
    }
  }, [sorting]);

  // Sincronizar término de búsqueda cuando cambie el valor inicial desde el servidor
  useEffect(() => {
    setSearch((prev) => (prev === initialSearch ? prev : initialSearch));
  }, [initialSearch]);

  // Recordar la última cadena de parámetros enviados para evitar navegaciones redundantes
  const previousParamsRef = useRef<string>('');

  // Construir los parámetros de solicitud basados en el estado actual (memoizado)
  const requestParams = useMemo(() => {
    const params = buildParams({
      pageIndex: pagination.pageIndex,
      pageSize: pagination.pageSize,
      sorting,
      search: debouncedSearch,
    });
    return params;
  }, [
    pagination.pageIndex,
    pagination.pageSize,
    sorting,
    debouncedSearch,
    buildParams,
    // eslint-disable-next-line react-hooks/exhaustive-deps
    ...extraDeps,
  ]);

  // Efecto central: realiza la navegación con Inertia cuando cambian los parámetros memoizados
  useEffect(() => {
    const paramsString = JSON.stringify(requestParams);
    if (paramsString === previousParamsRef.current) return;

    const timer = setTimeout(() => {
      setIsLoading(true);
      router.get(routeUrl, requestParams as RequestPayload, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        // Recarga parcial: refresca solo las props indicadas
        only: partialProps ?? [],
        onFinish: () => {
          setIsLoading(false);
          previousParamsRef.current = paramsString;
        },
        onError: (err) => {
          setIsLoading(false);
          onError?.(err);
        },
      });
    }, 150);

    return () => {
      clearTimeout(timer);
    };
  }, [routeUrl, requestParams, onError, partialProps]);

  // Cambiar página o tamaño de página para paginación de servidor (memoizado)
  const handleServerPaginationChange = useCallback((pageIndex: number, pageSize: number) => {
    startTransition(() => {
      setPagination((prev) => {
        if (pageSize && pageSize !== prev.pageSize) {
          return { pageIndex: 0, pageSize };
        }
        return { pageIndex, pageSize: prev.pageSize };
      });
    });
  }, []);

  return {
    pagination,
    setPagination: setPaginationTransition,
    sorting,
    setSorting: setSortingTransition,
    search,
    setSearch: setSearchTransition,
    isLoading,
    handleServerPaginationChange,
  };
}
