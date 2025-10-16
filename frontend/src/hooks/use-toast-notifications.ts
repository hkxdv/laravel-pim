import { useTheme } from '@/providers/theme-provider';
import { AlertCircle, AlertTriangle, CheckCircle, Info } from 'lucide-react';
import React, { useCallback, useMemo } from 'react';
import { toast } from 'sonner';

interface ToastOptions {
  duration?: number;
  position?:
    | 'top-left'
    | 'top-right'
    | 'top-center'
    | 'bottom-left'
    | 'bottom-right'
    | 'bottom-center';
  id?: string;
  icon?: React.ReactNode;
  description?: React.ReactNode;
}

/**
 * Hook personalizado para manejar notificaciones toast con una configuración consistente
 */
export function useToastNotifications() {
  // Obtener el tema actual
  const { theme } = useTheme();
  const isDarkMode =
    theme === 'dark' ||
    (theme === 'system' && globalThis.window.matchMedia('(prefers-color-scheme: dark)').matches);

  // Opciones comunes para las notificaciones toast
  const defaultOptions: ToastOptions = useMemo(
    () => ({
      duration: 5000,
      position: 'top-right',
    }),
    [],
  );

  /**
   * Muestra una notificación de éxito
   */
  const showSuccess = useCallback(
    (message: string, options?: ToastOptions) => {
      toast.success(message, {
        ...defaultOptions,
        icon: React.createElement(CheckCircle, {
          className: `h-5 w-5 ${isDarkMode ? 'text-emerald-400' : 'text-emerald-600'}`,
        }),
        ...options,
      });
    },
    [defaultOptions, isDarkMode],
  );

  /**
   * Muestra una notificación de error
   */
  const showError = useCallback(
    (message: string, options?: ToastOptions) => {
      toast.error(message, {
        ...defaultOptions,
        icon: React.createElement(AlertCircle, {
          className: `h-5 w-5 ${isDarkMode ? 'text-red-400' : 'text-red-600'}`,
        }),
        ...options,
      });
    },
    [defaultOptions, isDarkMode],
  );

  /**
   * Muestra una notificación de información
   */
  const showInfo = useCallback(
    (message: string, options?: ToastOptions) => {
      toast.info(message, {
        ...defaultOptions,
        icon: React.createElement(Info, {
          className: `h-5 w-5 ${isDarkMode ? 'text-blue-400' : 'text-blue-600'}`,
        }),
        ...options,
      });
    },
    [defaultOptions, isDarkMode],
  );

  /**
   * Muestra una notificación de advertencia
   */
  const showWarning = useCallback(
    (message: string, options?: ToastOptions) => {
      toast.warning(message, {
        ...defaultOptions,
        icon: React.createElement(AlertTriangle, {
          className: `h-5 w-5 ${isDarkMode ? 'text-amber-400' : 'text-amber-600'}`,
        }),
        ...options,
      });
    },
    [defaultOptions, isDarkMode],
  );

  /**
   * Muestra una notificación de error para un campo específico del formulario
   */
  const showFieldError = useCallback(
    (field: string, message: string) => {
      toast.error(`Error: ${message}`, {
        ...defaultOptions,
        id: `error-${field}`,
        duration: 6000,
        icon: React.createElement(AlertCircle, {
          className: `h-5 w-5 ${isDarkMode ? 'text-red-400' : 'text-red-600'}`,
        }),
      });
    },
    [defaultOptions, isDarkMode],
  );

  // Devolver referencias estables para evitar re-ejecuciones innecesarias en efectos
  return {
    showSuccess,
    showError,
    showInfo,
    showWarning,
    showFieldError,
  };
}
