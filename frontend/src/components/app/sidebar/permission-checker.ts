import type { User } from '@/types';
import { extractUserData } from '@/utils/user-data';

/** Roles con acceso total al sistema. */
const SUPER_ADMIN_ROLE = 'ADMIN';
const DEVELOPER_ROLE = 'DEV';
const PRIVILEGED_ROLES = new Set([SUPER_ADMIN_ROLE, DEVELOPER_ROLE]);

/**
 * Verifica de forma robusta si un usuario tiene un permiso específico.
 *
 * @remarks
 * Esta función es un pilar de la seguridad del frontend. Sigue una lógica de "denegación por defecto":
 * 1. Si no se requiere ningún permiso, se concede el acceso.
 * 2. Si no hay un objeto de usuario, se deniega el acceso.
 * 3. Extrae los datos del usuario y verifica que sea de tipo 'staff' y tenga un array de permisos.
 * 4. **Excepción de Super-Admin**: Si el usuario tiene un rol privilegiado (ej. 'ADMIN' o 'DEV'),
 *    se le concede acceso total, saltándose la comprobación de permisos específicos.
 * 5. Finalmente, comprueba si la lista de permisos del usuario incluye el/los permiso(s) requerido(s).
 *
 * @param user - El objeto de usuario, que puede ser nulo.
 * @param permission - Un permiso (string) o una lista de permisos (string[]) a verificar.
 * @param requireAll - Si es `true` y `permission` es un array, el usuario debe tener *todos* los permisos. Por defecto es `false`.
 * @returns `true` si el usuario tiene el permiso, `false` en caso contrario.
 *
 */
export const userHasPermission = (
  user: User | null,
  permission?: string | string[],
  requireAll = false,
): boolean => {
  // 1. Si no se requiere ningún permiso, el acceso está permitido.
  if (!permission || (Array.isArray(permission) && permission.length === 0)) {
    return true;
  }

  // 2. Si no hay usuario, el acceso se deniega.
  if (!user) {
    return false;
  }

  // 3. Extraer y validar la estructura de datos del usuario.
  const userData = extractUserData(user);

  if (!userData || !Array.isArray(userData.permissions)) {
    return false;
  }
  // A partir de aquí, `userData` es un usuario de tipo 'staff' con un array de permisos.

  // 4. Conceder acceso automático a roles privilegiados.
  if (Array.isArray(userData.roles)) {
    const hasPrivilegedRole = userData.roles.some((role) => PRIVILEGED_ROLES.has(role.name));
    if (hasPrivilegedRole) {
      return true;
    }
  }

  // 5. Comprobar el/los permiso(s) específico(s).
  const { permissions: userPermissions } = userData;
  if (Array.isArray(permission)) {
    // Si se requiere que se cumplan TODOS los permisos de la lista.
    if (requireAll) {
      return permission.every((p) => userPermissions.includes(p));
    }
    // Si se requiere que se cumpla AL MENOS UNO de los permisos de la lista.
    return permission.some((p) => userPermissions.includes(p));
  }

  // Si se requiere un solo permiso
  return userPermissions.includes(permission);
};
