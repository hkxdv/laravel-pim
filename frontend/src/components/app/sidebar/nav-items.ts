import { type NavItemDefinition, type User } from '@/types';

/**
 * Define la estructura de navegación principal de la barra lateral (sidebar).
 *
 * @remarks
 * Esta función retorna un array de objetos `NavItemDefinition` que construye el menú principal.
 * Cada objeto puede representar un enlace, un encabezado de sección o un grupo de enlaces.
 *
 * - `title`: El texto visible del elemento.
 * - `href`: El nombre de la ruta (manejado por Ziggy) a la que navegar. Requerido para enlaces.
 * - `icon`: El nombre de un ícono de `lucide-react`.
 * - `permission`: Permiso(s) necesarios para ver este elemento. La lógica en `process-nav-items.ts` lo utiliza para filtrar el menú.
 *   - `undefined`: Visible para todos.
 *   - `string`: Requiere un permiso específico.
 *   - `string[]`: Requiere al menos uno de los permisos de la lista.
 * - `requireAllPermissions`: Si es `true` y `permission` es un array, el usuario debe tener *todos* los permisos para ver el elemento.
 * - `isHeader`: Si es `true`, se renderiza como un encabezado de sección no interactivo.
 * - `items`: Un array de sub-elementos `NavItemDefinition` para crear menús desplegables.
 *
 */
// El parámetro `user` se mantiene por compatibilidad futura; prefijado con `_` para evitar lint.
export const getMainNavItemsDefinition = (_user: User | null): NavItemDefinition[] => {
  return [
    {
      title: 'Inicio',
      href: route('internal.dashboard'),
      icon: 'Home',
      permission: undefined as unknown as string | string[],
    },
  ];
};

/**
 * Define la estructura de navegación del pie de página de la barra lateral.
 *
 * @remarks
 * Generalmente se usa para enlaces secundarios como 'Configuración' o 'Cerrar Sesión'.
 * Sigue la misma estructura `NavItemDefinition` que `getMainNavItemsDefinition()`.
 * Actualmente está vacío, pero se puede poblar según sea necesario.
 */
export const footerNavItemsDefinition: NavItemDefinition[] = [];
