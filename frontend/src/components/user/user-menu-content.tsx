import {
  DropdownMenuGroup,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import { UserInfo } from '@/components/user/user-info';
import { useMobileNavigation } from '@/hooks/use-mobile-navigation';
import { type User } from '@/types';
import { Link, router } from '@inertiajs/react';
import { LogOut, Settings } from 'lucide-react';

interface UserMenuContentProps {
  user: User;
}

// Determinar la ruta de logout (solo staff); no depende de estado local.
const getLogoutRoute = (): string => route('logout');

// Mostrar opciones de configuración siempre para staff; función fuera del componente.
const shouldShowSettings = (): boolean => true;

export function UserMenuContent({ user }: Readonly<UserMenuContentProps>) {
  const cleanup = useMobileNavigation();

  const handleLogout = () => {
    cleanup();
    router.flushAll();
  };

  return (
    <>
      <DropdownMenuLabel className="p-0 font-normal">
        <div className="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
          <UserInfo user={user} showEmail={true} />
        </div>
      </DropdownMenuLabel>
      <DropdownMenuSeparator />

      {shouldShowSettings() && (
        <>
          <DropdownMenuGroup>
            <DropdownMenuItem asChild>
              <Link
                className="block w-full"
                href={route('internal.settings.profile.edit')}
                as="button"
                prefetch
                onClick={cleanup}
              >
                <Settings className="mr-2" />
                Configuración
              </Link>
            </DropdownMenuItem>
          </DropdownMenuGroup>
          <DropdownMenuSeparator />
        </>
      )}

      <DropdownMenuItem asChild>
        <Link
          className="block w-full"
          method="post"
          href={getLogoutRoute()}
          as="button"
          onClick={handleLogout}
        >
          <LogOut className="mr-2" />
          Cerrar sesión
        </Link>
      </DropdownMenuItem>
    </>
  );
}
