import type { Product } from '@/pages/modules/inventory/types/product';
import type {
  AuthData,
  BaseModulePageProps,
  BreadcrumbItem,
  NavItemDefinition,
  Paginated,
  User,
} from '@/types';
import type { PageProps } from '@inertiajs/core';

/**
 * Propiedades para la página del panel principal del Módulo Inventario.
 * Extiende las propiedades globales de página con datos específicos del módulo.
 *
 * Nota: `stats` es un objeto tipado por el backend y usado para construir
 * tarjetas de estadísticas en el frontend. Mantener nombres de campos estables.
 */
export interface InventoryIndexPageProps extends PageProps, BaseModulePageProps<object> {}

/**
 * Props para la página de listado de productos.
 */
export interface ProductListPageProps {
  products: Paginated<Product>;
  filters: {
    search?: string;
    sort_field?: string;
    sort_direction?: string;
    is_active?: boolean;
  };
  debug?: {
    /** Modo de búsqueda actual (p. ej. 'sqlite' o 'typesense') */
    search_mode?: string;
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
 * Props para la página de edición de producto.
 */
export interface ProductEditPageProps {
  product: Product;
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
 * Props para la página de creación de producto.
 */
export interface ProductCreatePageProps {
  product?: Product; // Presente cuando se crea exitosamente con preventRedirect
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
 * Props para la página de creación de movimiento de stock.
 */
export interface StockMovementCreatePageProps {
  movement?: {
    id: number;
    product_id: number;
    type: 'in' | 'out' | 'adjust';
    quantity: number | null;
    new_stock: number | null;
    notes?: string | null;
    performed_at?: string;
  };
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
