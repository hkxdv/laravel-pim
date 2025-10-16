import { router } from '@inertiajs/core';
import { useEffect, useRef, useState } from 'react';

interface Options {
  delayMs?: number;
}

/**
 * Hook que expone el estado de navegación de Inertia (start/finish) con un pequeño retraso configurable.
 * Úsalo para mostrar skeletons mientras se produce una navegación entre páginas.
 */
export function useNavigationProgress(options?: Options): boolean {
  const delayMs = options?.delayMs ?? 150;
  const [isNavigating, setIsNavigating] = useState(false);
  const timeoutRef = useRef<number | null>(null);

  useEffect(() => {
    const handleStart = () => {
      // Cancelar cualquier timeout pendiente y marcar navegación activa
      if (timeoutRef.current) {
        globalThis.clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
      setIsNavigating(true);
    };

    const handleFinish = () => {
      // Pequeño retraso para evitar parpadeos si la navegación es instantánea
      if (timeoutRef.current) {
        globalThis.clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
      // eslint-disable-next-line unicorn/prefer-global-this
      timeoutRef.current = window.setTimeout(() => {
        setIsNavigating(false);
        timeoutRef.current = null;
      }, delayMs);
    };

    const removeStartListener = router.on('start', handleStart);
    const removeFinishListener = router.on('finish', handleFinish);

    return () => {
      // Limpieza de listeners y timeouts
      removeStartListener();
      removeFinishListener();
      if (timeoutRef.current) {
        globalThis.clearTimeout(timeoutRef.current);
        timeoutRef.current = null;
      }
    };
  }, [delayMs]);

  return isNavigating;
}
