import type {
  AuthData,
  BaseModulePageProps,
  BreadcrumbItem,
  ModuleNavItem,
  NavItemDefinition,
  Paginated,
  StaffUser,
  User,
  UserRole,
} from '@/types';
import { type PageProps } from '@inertiajs/core';

/**
 * Define la estructura de un ítem del panel de administración.
 * Estos ítems se muestran como tarjetas clicables que navegan a diferentes secciones.
 */
export interface PanelItem {
  /** Nombre descriptivo del ítem, se muestra en la tarjeta. */
  name: string;
  /** Descripción breve de la funcionalidad del ítem. */
  description: string;
  /** Nombre de la ruta de Laravel a la que navegará el ítem. */
  route_name: string;
  /** Nombre del ícono de Lucide (opcional) a mostrar en la tarjeta. */
  icon?: string;
  /** Permiso de Spatie requerido (opcional) para ver/acceder al ítem. */
  permission?: string;
}

/**
 * Define la estructura de las estadísticas que se muestran en el panel de administración.
 */
export interface AdminDashboardStats {
  /** Número total de usuarios registrados en el sistema. */
  totalUsers: number;
  /** Número total de roles definidos en el sistema. */
  totalRoles: number;
  /** Número de acciones en la última semana. */
  recentActivityCount: number;
}

/**
 * Props para la página principal del panel de administración (`AdminIndex`).
 */
export interface AdminIndexPageProps extends PageProps, BaseModulePageProps<AdminDashboardStats> {
  /** Ítems de navegación de módulo conformes al contrato unificado. */
  panelItems?: ModuleNavItem[];
  /** Ítems de navegación principal para el layout. */
  mainNavItems?: NavItemDefinition[];
  /** Ítems de navegación de módulos para el layout. */
  moduleNavItems?: NavItemDefinition[];
  /** Ítems de navegación global para el layout (configuración). */
  globalNavItems?: NavItemDefinition[];
}

/**
 * Props para la página de listado de usuarios.
 */
export interface UserListPageProps {
  users: Paginated<StaffUser>;
  filters: {
    search?: string;
    role?: string;
    sort_field?: string;
    sort_direction?: string;
  };
  contextualNavItems?: NavItemDefinition[];
  breadcrumbs?: BreadcrumbItem[];
  auth: AuthData;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}

/**
 * Props para la página de edición de usuario.
 */
export interface UserEditPageProps {
  user: StaffUser;
  roles: UserRole[];
  auth: { user: User };
  contextualNavItems?: NavItemDefinition[];
  mainNavItems?: NavItemDefinition[];
  moduleNavItems?: NavItemDefinition[];
  globalNavItems?: NavItemDefinition[];
  breadcrumbs?: BreadcrumbItem[];
  _errors?: Record<string, string>;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}

/**
 * Props para la página de creación de usuario.
 */
export interface UserCreatePageProps {
  roles: UserRole[];
  auth: { user: User };
  contextualNavItems?: NavItemDefinition[];
  mainNavItems?: NavItemDefinition[];
  moduleNavItems?: NavItemDefinition[];
  globalNavItems?: NavItemDefinition[];
  breadcrumbs?: BreadcrumbItem[];
  _errors?: Record<string, string>;
  flash?: {
    success?: string | null;
    error?: string | null;
    info?: string | null;
    warning?: string | null;
  };
}

/**
 * Datos de usuario para formularios (creación/edición) mínimos.
 */
export interface UserFormData {
  /** Identificador numérico único del usuario (presente en edición, ausente en creación). */
  id?: number;
  /** Nombre completo del usuario. */
  name: string;
  /** Dirección de correo electrónico del usuario. */
  email: string;
  /** Array de roles asignados al usuario. */
  roles?: UserRole[];
}

/**
 * Identificador mínimo de usuario para diálogos/acciones.
 */
export interface UserIdentifier {
  id: number;
  name: string;
  email: string;
}

// Reexportar tipos globales útiles relacionados
export type {
  Role as AdminRole,
  UserListItem as AdminUserListItem,
  UserRole as AdminUserRole,
} from '@/types';
