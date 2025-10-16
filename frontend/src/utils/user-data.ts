import type { StaffUser, User } from '@/types';

const DEFAULT_USER_NAME = 'Usuario';

// Define un tipo para el objeto de usuario que puede venir anidado.
export type PotentiallyNestedUser = User | { data: User } | null | undefined;

/**
 * Extrae de forma segura el objeto de usuario principal, ya sea que esté anidado o no.
 * @param user - El objeto de usuario potencialmente anidado.
 * @returns El objeto `StaffUser`, o `null` si no se encuentra.
 */
export function extractUserData(user: PotentiallyNestedUser): StaffUser | null {
  // Si el usuario es nulo o indefinido, retorna nulo.
  if (!user) {
    return null;
  }

  // Si el usuario tiene una propiedad 'data' y es un objeto, asume que es la estructura anidada.
  if ('data' in user && typeof user.data === 'object' && user.data !== null) {
    const userData = user.data as StaffUser;

    // Validación adicional para asegurarse de que el objeto extraído tenga propiedades de usuario.
    if ('id' in userData && 'email' in userData) {
      return userData;
    }
  }

  // Si no es una estructura anidada, se asume que el objeto principal es el usuario.
  const userData = user as StaffUser;
  if ('id' in userData && 'email' in userData) {
    return userData;
  }

  // Si ninguna de las condiciones anteriores se cumple, retorna nulo.
  return null;
}

/**
 * Obtiene el nombre para mostrar de un usuario.
 *
 * @param user - El objeto de usuario (StaffUser).
 * @returns El nombre para mostrar del usuario.
 */
export function getUserDisplayName(user: PotentiallyNestedUser): string {
  const userData = extractUserData(user);

  if (!userData) {
    return DEFAULT_USER_NAME;
  }
  // Simplificado: solo StaffUser
  return userData.name || DEFAULT_USER_NAME;
}

/**
 * Obtiene las iniciales de un usuario para mostrarlas en un avatar.
 * @param user - El objeto de usuario (StaffUser).
 * @returns Un string con las iniciales del usuario.
 */
export function getUserInitials(user: PotentiallyNestedUser): string {
  const displayName = getUserDisplayName(user);
  if (!displayName || displayName === DEFAULT_USER_NAME) {
    return '?';
  }
  return displayName
    .split(' ')
    .map((n) => n[0])
    .slice(0, 2)
    .join('')
    .toUpperCase();
}

/**
 * Obtiene el nombre completo o identificador principal de un usuario.
 * @param user - El objeto de usuario (StaffUser).
 * @returns El nombre completo del usuario.
 */
export function getUserName(user: StaffUser | null): string {
  if (!user) {
    return 'Usuario Desconocido';
  }
  // Simplificado: solo StaffUser
  return user.name || DEFAULT_USER_NAME;
}
