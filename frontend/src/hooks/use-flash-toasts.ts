import { useEffect } from 'react';
import { useToastNotifications } from './use-toast-notifications';

interface FlashBag {
  success?: string;
  error?: string;
  info?: string;
  warning?: string;
}

/**
 * Hook para disparar toasts basados en el objeto flash de Inertia.
 * Internamente utiliza useToastNotifications para mostrar mensajes.
 */
export function useFlashToasts(flash?: FlashBag | null): void {
  const { showError, showInfo, showSuccess, showWarning } = useToastNotifications();

  useEffect(() => {
    if (!flash) return;

    if (flash.success) {
      showSuccess(flash.success);
    }
    if (flash.error) {
      showError(flash.error);
    }
    if (flash.info) {
      showInfo(flash.info);
    }
    if (flash.warning) {
      showWarning(flash.warning);
    }
  }, [flash, showError, showInfo, showSuccess, showWarning]);
}
