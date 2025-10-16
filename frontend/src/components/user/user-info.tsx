import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';
import { extractUserData } from '@/utils/user-data';

/**
 * Componente para mostrar la información básica de un usuario, incluyendo avatar, nombre e email (opcional).
 * Se adapta dinámicamente si el usuario es un StaffUser.
 *
 * @param user - El objeto de usuario (StaffUser).
 * @param showEmail - Booleano para indicar si se debe mostrar el email. Por defecto es falso.
 * @returns El elemento JSX con la información del usuario.
 */
export function UserInfo({
  user,
  showEmail = false,
}: Readonly<{ user: User; showEmail?: boolean }>) {
  const getInitials = useInitials();

  const userData = extractUserData(user);
  if (!userData) return null;

  // Simplificado: solo existe StaffUser
  const displayName: string | undefined = userData.name;
  const displayEmail: string | undefined =
    typeof userData.email === 'string' && userData.email.trim() !== '' ? userData.email : undefined;
  const avatarSrc: string | undefined = userData.avatar;
  const initials = getInitials(displayName);

  return (
    <>
      <Avatar className="h-8 w-8 overflow-hidden rounded-full">
        {avatarSrc && <AvatarImage src={avatarSrc} alt={displayName} />}
        <AvatarFallback className="rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
          {initials}
        </AvatarFallback>
      </Avatar>
      <div className="grid flex-1 text-left text-sm leading-tight">
        <span className="truncate font-medium" title={displayName}>
          {displayName}
        </span>
        {showEmail && displayEmail && (
          <span className="text-muted-foreground truncate text-xs" title={displayEmail}>
            {displayEmail}
          </span>
        )}
      </div>
    </>
  );
}
