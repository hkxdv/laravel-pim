import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem, NavItemDefinition, User } from '@/types';
import type { ReactNode } from 'react';

/**
 * Props extendidas para el layout principal de la aplicación.
 * @property {User | null} user - Usuario autenticado
 * @property {ReactNode} children - Contenido de la página
 * @property {BreadcrumbItem[]} [breadcrumbs] - Migas de pan para navegación
 * @property {NavItemDefinition[]} [mainNavItems] - Ítems de navegación principal
 * @property {NavItemDefinition[]} [moduleNavItems] - Ítems de navegación de módulos
 * @property {NavItemDefinition[]} [contextualNavItems] - Ítems de navegación contextual
 * @property {NavItemDefinition[]} [globalNavItems] - Ítems de navegación global (configuración)
 * @property {string} [pageTitle] - Título de la página.
 * @property {string} [pageDescription] - Descripción de la página.
 * @property {ReactNode} [headerActions] - Acciones para la cabecera de la página.
 */
export interface ExtendedAppLayoutProps {
  user: User | null;
  children: ReactNode;
  breadcrumbs?: BreadcrumbItem[];
  mainNavItems?: NavItemDefinition[];
  moduleNavItems?: NavItemDefinition[];
  contextualNavItems?: NavItemDefinition[];
  globalNavItems?: NavItemDefinition[];
  headerActions?: ReactNode;
  pageTitle?: string;
  pageDescription?: string;
  header?: ReactNode;
}

/**
 * Layout principal de la aplicación, incluye barra lateral y navegación contextual.
 * @param {ExtendedAppLayoutProps} props
 */
const AppLayout = ({
  user,
  children,
  breadcrumbs,
  mainNavItems,
  moduleNavItems,
  contextualNavItems,
  globalNavItems,
  headerActions,
  ...props
}: ExtendedAppLayoutProps) => (
  <AppLayoutTemplate
    user={user}
    breadcrumbs={breadcrumbs ?? []}
    mainNavItems={mainNavItems ?? []}
    moduleNavItems={moduleNavItems ?? []}
    contextualNavItems={contextualNavItems ?? []}
    globalNavItems={globalNavItems ?? []}
    headerActions={headerActions}
    {...props}
  >
    {children}
  </AppLayoutTemplate>
);

export default AppLayout;
