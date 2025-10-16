import type { IconName } from '@/utils/lucide-icons';
import type { LucideIcon } from 'lucide-react';

/**
 * Props base compartidas para páginas de módulos.
 * Incluye elementos de navegación contextual, items del panel principal,
 * y metadatos como título, descripción y estadísticas tipadas.
 */
export interface BaseModulePageProps<TStats = unknown> {
  /** Ítems de navegación contextual para el sidebar, proporcionados por el backend */
  contextualNavItems?: NavItemDefinition[];
  /** Ítems de navegación del panel principal del módulo */
  panelItems?: ModuleNavItem[];
  /** Breadcrumbs Proporcionadas por el backend */
  breadcrumbs?: BreadcrumbItem[];
  /** Título de la página proporcionado por el backend */
  pageTitle?: string;
  /** Descripción de la página proporcionada por el backend */
  description?: string;
  /** Estadísticas específicas del módulo */
  stats?: TStats;
}

/**
 * Interfaz base para modelos que tienen marcas de tiempo de creación y actualización.
 */
export interface Auditable {
  created_at: string | null;
  updated_at: string | null;
}

/**
 * Interfaz base para todas las entidades del modelo que tienen un ID.
 */
export interface BaseEntity extends Auditable {
  id: number;
}

/**
 * Representa la estructura de datos paginados después de ser procesada en el frontend.
 * Esta forma es más conveniente para componentes de tabla y paginación.
 * @template T El tipo de los elementos en los datos paginados.
 */
export interface Paginated<T> {
  /** El array de elementos para la página actual. */
  data: T[];
  /** Metadatos de la paginación. */
  meta: PaginatedMeta;
  /** Enlaces para la navegación de la paginación. */
  links: PaginatedLinks;
}

export interface PaginationLink {
  url: string | null;
  label: string;
  active: boolean;
}

/**
 * Metadatos detallados de una respuesta paginada.
 */
export interface PaginatedMeta {
  /** El número de la página actual. */
  current_page: number;
  /** El índice del primer elemento en la página. */
  from: number;
  /** El número de la última página. */
  last_page: number;
  /** Un array de objetos de enlace para la paginación. */
  links: PaginationLink[];
  /** La ruta base para las URLs de paginación. */
  path: string;
  /** El número de elementos por página. */
  per_page: number;
  /** El índice del último elemento en la página. */
  to: number;
  /** El número total de elementos en todas las páginas. */
  total: number;
}

/**
 * Enlaces de navegación para una respuesta paginada.
 */
export interface PaginatedLinks {
  /** URL de la primera página. */
  first: string;
  /** URL de la última página. */
  last: string;
  /** URL de la página anterior, o null si no hay. */
  prev: string | null;
  /** URL de la página siguiente, o null si no hay. */
  next: string | null;
}

/**
 * Campos base compartidos por todos los tipos de usuarios.
 */
export type BaseUser = BaseEntity;

/**
 * Representa un rol de usuario en el sistema.
 */
export interface Role {
  /** El ID único del rol. */
  id: number;
  /** El nombre del rol. */
  name: string;
  /** Permite propiedades adicionales para flexibilidad. */
  [key: string]: unknown;
}

/**
 * Representa a un usuario del personal interno (administradores, etc.).
 */
export interface StaffUser extends BaseUser {
  /** El nombre completo del usuario del personal. */
  name: string;
  /** El correo electrónico del usuario. */
  email: string;
  /** La URL del avatar del usuario. */
  avatar?: string;
  /** La fecha y hora en que se verificó el correo electrónico. */
  email_verified_at: string | null;
  /** Lista de permisos directos del usuario. */
  permissions?: string[];
  /** Lista de roles asignados al usuario. */
  roles?: Role[];
  /** El tipo de usuario, fijo a 'staff'. */
  user_type: 'staff';
  /** Permite propiedades adicionales para flexibilidad. */
  [key: string]: unknown;
}

// Usuario único soportado actualmente
export type User = StaffUser;

/**
 * Alias para el tipo `Role` para mayor claridad semántica.
 */
export type UserRole = Role;

/**
 * Representa un objeto de usuario simplificado, ideal para listas y diálogos.
 * Asegura que las propiedades esenciales para la visualización estén presentes.
 */
export interface UserListItem {
  /** El ID único del usuario. */
  id: number;
  /** El nombre completo del usuario. */
  name: string;
  /** El correo electrónico del usuario. */
  email: string;
  /** Los roles asignados al usuario. */
  roles?: UserRole[];
}

/**
 * Define la estructura del objeto `auth` proporcionado por Inertia.
 */
export interface AuthData {
  /** El objeto de usuario autenticado, o `null` si es un invitado. */
  user: { data: User } | null;
  /** Un objeto que contiene las habilidades/permisos del usuario. */
  can?: {
    /** Si el usuario puede suplantar a otros. */
    impersonate?: boolean;
    /** Permite cualquier otro permiso booleano. */
    [key: string]: boolean | undefined;
  };
  /** Indica si el usuario está actualmente suplantando a otro. */
  impersonate?: boolean;
}

/**
 * Props base para páginas de Inertia.
 */
export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
  auth: AuthData;
};

/**
 * Define un elemento individual en el rastro de migas de pan (breadcrumbs).
 */
export interface BreadcrumbItem {
  /** El texto a mostrar para el enlace. */
  title: string;
  /** La URL a la que apunta el enlace. */
  href: string;
}

/**
 * Define la estructura de un elemento de navegación para menús y barras laterales.
 */
export interface NavItemDefinition {
  /** El texto a mostrar para el elemento de navegación. */
  title: string;
  /** La URL a la que navega el elemento. */
  href: string;
  /** El componente de ícono a mostrar (de `lucide-react`) o un string con su nombre. */
  icon?: LucideIcon | string | null;
  /** Si el elemento debe aparecer como activo. */
  isActive?: boolean;
  /** El permiso o lista de permisos necesarios para ver este elemento. */
  permission?: string | string[];
  /** Si se requieren todos los permisos de la lista para ver el elemento. `false` por defecto (requiere al menos uno). */
  requireAllPermissions?: boolean;
  /** Si el elemento es un encabezado de sección no clickeable. */
  isHeader?: boolean;
  /** Un array de sub-elementos de navegación anidados. */
  children?: NavItemDefinition[];
  /** Si el enlace apunta a un sitio externo. */
  external?: boolean;
  /** El atributo `target` para el enlace (ej. `_blank`). */
  target?: '_blank' | '_self' | '_parent' | '_top';
  /** Una función a ejecutar al hacer clic, en lugar de navegar. */
  onClick?: () => void;
}

/**
 * Estructuras de navegación de módulos, usadas por tarjetas y contenidos de índice.
 * Centralizadas aquí para que componentes y páginas consuman una única fuente de verdad.
 */
export interface BaseModuleNavItem {
  /** Nombre a mostrar en la tarjeta de navegación */
  name: string;
  /** Descripción breve de la funcionalidad */
  description: string;
  /** Nombre del icono de Lucide (opcional) */
  icon?: IconName;
  /** Clave del permiso requerido (opcional) */
  permission?: string;
}

/** Ítem que usa nombre de ruta de Laravel para navegar */
export interface RouteNameModuleNavItem extends BaseModuleNavItem {
  route_name: string;
  route?: never;
  href?: never;
}

/** Ítem que usa una ruta directa (URL interna) */
export interface DirectRouteModuleNavItem extends BaseModuleNavItem {
  route: string;
  route_name?: never;
  href?: never;
}

/** Ítem que usa un `href` absoluto o relativo */
export interface HrefModuleNavItem extends BaseModuleNavItem {
  href: string;
  route_name?: never;
  route?: never;
}

/** Tipo unificado para navegación de módulos */
export type ModuleNavItem = RouteNameModuleNavItem | DirectRouteModuleNavItem | HrefModuleNavItem;

/**
 * Representa un elemento de navegación procesado, listo para ser renderizado.
 * El ícono ha sido convertido a un componente y se han aplicado las lógicas de permisos.
 */
export interface ProcessedNavItem extends Omit<NavItemDefinition, 'icon' | 'children'> {
  /** El componente de ícono `LucideIcon` o `null`. */
  icon: LucideIcon | null;
  /** Sub-elementos anidados ya procesados. */
  children?: ProcessedNavItem[];
}

/**
 * Representa la estructura cruda de una respuesta paginada de la API de Laravel.
 * Esta es la forma en que los datos llegan del backend antes de cualquier transformación.
 * @template T El tipo de los elementos en los datos paginados.
 */
export interface PaginatedResponse<T> {
  /** El array de elementos para la página actual. */
  data: T[];
  /** El número de la página actual. */
  current_page: number;
  /** La URL completa de la primera página. */
  first_page_url: string;
  /** El índice del primer elemento en la página. */
  from: number;
  /** El número de la última página. */
  last_page: number;
  /** La URL completa de la última página. */
  last_page_url: string;
  /** Un array de objetos de enlace para la paginación. */
  links: PaginationLink[];
  /** La URL de la página siguiente, o null si no hay. */
  next_page_url: string | null;
  /** La ruta base para las URLs de paginación. */
  path: string;
  /** El número de elementos por página. */
  per_page: number;
  /** La URL de la página anterior, o null si no hay. */
  prev_page_url: string | null;
  /** El índice del último elemento en la página. */
  to: number;
  /** El número total de elementos en todas las páginas. */
  total: number;
}
