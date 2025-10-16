import { Bell } from 'lucide-react';
import React, { useEffect } from 'react';
import { useToastNotifications } from './use-toast-notifications';

/**
 * Propiedades para el hook `useWelcomeToast`.
 */
interface UseWelcomeToastProps {
  /** El nombre del usuario a mostrar en el saludo. */
  userName: string;
  /** Un booleano opcional para controlar si el toast debe mostrarse. Por defecto es `true`. */
  shouldShowToast?: boolean;
  /** Indica si estamos en el dashboard principal. Por defecto es `false`. */
  isMainDashboard?: boolean;
}

/**
 * Hook personalizado que muestra un toast de bienvenida único por sesión de navegador.
 *
 * @remarks
 * Este hook utiliza `sessionStorage` para recordar si el mensaje ya ha sido mostrado
 * durante la sesión actual del usuario, evitando así repeticiones.
 *
 * Es seguro para su uso en entornos de renderizado en el servidor (SSR), ya que toda
 * la lógica que interactúa con `sessionStorage` y `toast` se ejecuta únicamente en el cliente.
 *
 * @param props - Las propiedades para configurar el hook: `userName`, `shouldShowToast`, y `isMainDashboard`.
 */
export function useWelcomeToast({
  userName,
  shouldShowToast = true,
  isMainDashboard = false,
}: UseWelcomeToastProps): void {
  const { showSuccess } = useToastNotifications();

  useEffect(() => {
    // Se asegura de que el código solo se ejecute en el cliente, si la condición lo permite y si estamos en el dashboard principal.
    // eslint-disable-next-line unicorn/no-typeof-undefined
    if (!shouldShowToast || !isMainDashboard || typeof globalThis.window === 'undefined') {
      return;
    }

    const sessionKey = 'welcome_toast_shown';
    const hasBeenShown = globalThis.sessionStorage.getItem(sessionKey);

    // Si el toast ya se mostró en esta sesión, no hacer nada.
    if (hasBeenShown) {
      return;
    }

    const title = `¡Bienvenido, ${userName}!`;
    const description = 'Explora las funcionalidades disponibles en el sistema.';

    // Muestra el toast de éxito con la configuración deseada.
    showSuccess(title, {
      icon: React.createElement(Bell, { className: 'h-4 w-4' }),
      description,
    });

    // Marca el toast como mostrado para esta sesión.
    globalThis.sessionStorage.setItem(sessionKey, 'true');
  }, [userName, shouldShowToast, isMainDashboard, showSuccess]);
}
