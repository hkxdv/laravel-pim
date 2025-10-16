import { type NavItemDefinition, type ProcessedNavItem, type User } from '@/types';
import { getLucideIcon } from '@/utils/lucide-icons';

import { userHasPermission } from './permission-checker';

/**
 * Procesa de forma recursiva una lista de elementos de navegación (`NavItemDefinition`).
 *
 * @remarks
 * Esta función realiza dos tareas principales:
 * 1. **Filtrado por Permisos**: Elimina los elementos para los que el usuario no tiene permiso.
 *    Si un elemento "padre" se queda sin "hijos" visibles después del filtrado, el padre también se elimina
 *    para evitar menús desplegables vacíos. Utiliza `userHasPermission` y puede gestionar
 *    requisitos de permisos complejos (ver `NavItemDefinition`).
 * 2. **Mapeo de Datos**: Convierte propiedades de la definición a valores listos para renderizar:
 *    - `icon` (string) se transforma en un componente de ícono (`LucideIcon`).
 *    - `href` (nombre de ruta) se transforma en una URL real usando `route()`.
 *
 * @param items - El array de `NavItemDefinition` a procesar.
 * @param user - El objeto de usuario actual para la verificación de permisos.
 * @returns Un nuevo array de `ProcessedNavItem` procesado y listo para ser renderizado.
 */
export const processNavItems = (
  items: NavItemDefinition[],
  user: User | null,
): ProcessedNavItem[] => {
  const processedItems: ProcessedNavItem[] = [];

  for (const item of items) {
    // 1. Primero, verificar si el usuario tiene permiso para el elemento actual.
    const hasPermission = userHasPermission(user, item.permission, item.requireAllPermissions);
    if (!hasPermission) {
      // Si no hay permiso, se omite el elemento y todos sus hijos.
      continue;
    }

    // 2. Si el elemento tiene hijos, procesarlos de forma recursiva.
    let processedChildren: ProcessedNavItem[] | undefined;
    if (item.children && item.children.length > 0) {
      processedChildren = processNavItems(item.children, user);
    }

    // 3. Un elemento se incluye si es un enlace directo o si es un grupo
    //    que, después de filtrar, todavía tiene hijos visibles.
    const isDirectLink = !!item.href;
    const hasVisibleChildren = (processedChildren?.length ?? 0) > 0;

    if (isDirectLink || hasVisibleChildren) {
      let finalHref = '#';
      if (item.href) {
        finalHref =
          item.href.startsWith('http') || item.href === '#' ? item.href : route(item.href);
      }

      processedItems.push({
        ...item,
        // 4. Mapear las propiedades al formato final para renderizado.
        children: processedChildren ?? [],
        href: finalHref,
        icon: getLucideIcon(item.icon),
      });
    }
  }

  return processedItems;
};
