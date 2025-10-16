import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  useSidebar,
} from '@/components/ui/sidebar';
import { UserInfo } from '@/components/user/user-info';
import { UserMenuContent } from '@/components/user/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import { type User } from '@/types';
import { ChevronsUpDown } from 'lucide-react';

/**
 * Props para el componente NavUser.
 */
interface NavUserProps {
  user: User | null;
}

/**
 * Componente de navegación de usuario para el sidebar.
 * Muestra información del usuario actual y opciones del menú desplegable.
 */
export function NavUser({ user }: Readonly<NavUserProps>) {
  const { state } = useSidebar();
  const isMobile = useIsMobile();

  // Si no hay usuario, no renderizamos nada
  if (!user) {
    return null;
  }

  let side: 'bottom' | 'left' | 'right';

  if (isMobile) {
    side = 'bottom';
  } else {
    side = state === 'collapsed' ? 'left' : 'bottom';
  }

  return (
    <SidebarMenu>
      <SidebarMenuItem>
        <DropdownMenu>
          <DropdownMenuTrigger asChild>
            <SidebarMenuButton
              size="lg"
              className="text-sidebar-accent-foreground data-[state=open]:bg-sidebar-accent group"
            >
              <UserInfo user={user} />
              <ChevronsUpDown className="ml-auto size-4" />
            </SidebarMenuButton>
          </DropdownMenuTrigger>
          <DropdownMenuContent
            className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg"
            align="end"
            side={side}
          >
            <UserMenuContent user={user} />
          </DropdownMenuContent>
        </DropdownMenu>
      </SidebarMenuItem>
    </SidebarMenu>
  );
}
