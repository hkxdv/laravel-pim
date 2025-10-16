import { NavFooter } from '@/components/app/nav/nav-footer';
import { NavMain } from '@/components/app/nav/nav-main';
import { NavUser } from '@/components/app/nav/nav-user';
import {
  Sidebar,
  SidebarContent,
  SidebarFooter,
  SidebarHeader,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarSeparator,
} from '@/components/ui/sidebar';
import { type NavItemDefinition, type User } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import AppLogo from './app-logo';
import { footerNavItemsDefinition, getMainNavItemsDefinition } from './sidebar/nav-items';
import { processNavItems } from './sidebar/process-nav-items';

/**
 * Props para el componente AppSidebar.
 * @property {NavItem[]} [contextualNavItems] - Ítems de navegación contextual
 */
interface AppSidebarProps {
  user: User | null;
  mainNavItems?: NavItemDefinition[];
  moduleNavItems?: NavItemDefinition[];
  contextualNavItems?: NavItemDefinition[];
  globalNavItems?: NavItemDefinition[];
}

/**
 * Sidebar principal de la aplicación, muestra navegación principal y contextual.
 * @param {AppSidebarProps} props
 */
export function AppSidebar({
  user,
  mainNavItems = [],
  moduleNavItems = [],
  contextualNavItems = [],
  globalNavItems = [],
}: Readonly<AppSidebarProps>) {
  // Obtener información de la página actual
  const { component, url } = usePage();

  // Determinar si estamos en un módulo específico
  const isModulePage = component.startsWith('modules/');
  const moduleSlug = isModulePage ? component.split('/')[1] : '';

  // Determinar si estamos en la página principal de un módulo (panel del módulo)
  const isModulePanel = isModulePage && url.endsWith(`/internal/${moduleSlug}`);

  // Procesar el ítem de inicio definido estáticamente
  const homeNavItems = processNavItems(getMainNavItemsDefinition(user), user);

  // Procesar elementos de navegación recibidos del backend
  const processedMainNavItems = processNavItems(mainNavItems, user);

  // Crear un Set con los títulos de los ítems de navegación principal para filtrado eficiente
  const mainNavTitles = new Set(mainNavItems.map((item) => (item.title || '').toLowerCase()));

  // Filtrar los módulos para evitar duplicación con el módulo actual y con la navegación principal
  const filteredModuleNavItems = moduleNavItems.filter((item) => {
    // Si estamos en una página de módulo, filtrar el módulo actual
    if (isModulePage) {
      const itemHref = item.href || '';
      return (
        !itemHref.includes(`/modules/${moduleSlug}`) &&
        !itemHref.endsWith(`/internal/${moduleSlug}`)
      );
    }

    // Si no estamos en un módulo, verificar si el ítem ya está en la navegación principal
    const itemTitle = (item.title || '').toLowerCase();
    return !mainNavTitles.has(itemTitle); // No mostrar en módulos si ya está en navegación principal
  });

  const processedModuleNavItems = processNavItems(filteredModuleNavItems, user);

  // Procesar elementos de navegación contextual
  // Cuando estamos en el panel principal de un módulo, no mostramos ítems contextuales
  // que apunten al mismo módulo
  const filteredContextualItems = isModulePanel
    ? contextualNavItems.filter((item) => {
        const itemHref = item.href || '';
        return !itemHref.endsWith(`/internal/${moduleSlug}`);
      })
    : contextualNavItems;

  const processedContextualNavItems = processNavItems(filteredContextualItems, user);

  // Procesar elementos de navegación global
  const processedGlobalNavItems = processNavItems(globalNavItems, user);

  // Determinar la etiqueta para los ítems contextuales
  const contextualNavLabel = isModulePage ? 'Secciones' : 'Módulos';

  // Determinar qué ítems mostrar en la sección de módulos/secciones
  const navItemsToShow = isModulePage ? processedContextualNavItems : processedModuleNavItems;

  return (
    <Sidebar collapsible="icon" variant="inset">
      <SidebarHeader>
        <SidebarMenu>
          <SidebarMenuItem>
            <SidebarMenuButton size="lg" asChild>
              <Link href={route('internal.dashboard')}>
                <AppLogo />
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        </SidebarMenu>
      </SidebarHeader>

      <SidebarContent className="mt-2 flex flex-1 flex-col gap-y-1 overflow-y-auto">
        {/* Navegación principal siempre visible (Inicio + ítems principales) */}
        <NavMain items={[...homeNavItems, ...processedMainNavItems]} />

        {/* Mostrar ítems contextuales como Secciones o Módulos según corresponda */}
        {navItemsToShow.length > 0 && (
          <>
            <SidebarSeparator className="my-2.5" />
            <NavMain items={navItemsToShow} label={contextualNavLabel} />
          </>
        )}

        {/* Ítems de configuración global */}
        {processedGlobalNavItems.length > 0 && (
          <>
            <SidebarSeparator className="my-2.5" />
            <NavMain items={processedGlobalNavItems} label="Configuración" />
          </>
        )}
      </SidebarContent>

      <SidebarFooter>
        <NavFooter items={footerNavItemsDefinition} className="mt-auto" />
        <NavUser user={user} />
      </SidebarFooter>
    </Sidebar>
  );
}
