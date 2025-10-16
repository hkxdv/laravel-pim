import type { BreadcrumbItem } from '@/types';
import { route } from 'ziggy-js';

/**
 * Crea un arreglo de breadcrumbs estándar para páginas de índice de módulos.
 * @param routeName Nombre de la ruta (Ziggy) hacia la página actual.
 * @param title Título a mostrar en el breadcrumb actual.
 */
export function createBreadcrumbs(routeName: string, title: string): BreadcrumbItem[] {
  return [
    {
      title,
      href: route(routeName),
    },
  ];
}
