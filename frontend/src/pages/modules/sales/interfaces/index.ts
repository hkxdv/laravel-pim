import type { BaseModulePageProps, Paginated } from '@/types';
import type { PageProps } from '@inertiajs/core';

/**
 * Propiedades para la página del panel principal del Módulo de Ventas.
 * Extiende las propiedades globales de página con datos específicos del módulo.
 */
export interface SalesIndexPageProps
  extends PageProps,
    BaseModulePageProps<{
      // Props de ej. para las stats del módulo
      /** Número total de solicitudes registradas */
      totalRequests?: number;
      /** Total de órdenes registradas */
      ordersTotal?: number;
      /** Órdenes entregadas */
      deliveredOrders?: number;
      /** Suma de totales de órdenes */
      sumTotals?: number;
    }> {}

export interface OrderItem {
  /** Identificador del ítem de la orden */
  id: number;
  sales_order_id: number;
  product_id: number;
  /** Cantidad del producto en la orden */
  qty: number;
  /** Precio unitario del producto en el momento de la orden */
  price: number | string;
  created_at: string | null;
  updated_at: string | null;
  product?: {
    id: number;
    name?: string;
    sku?: string | null;
    stock?: number;
  };
}

export interface Order {
  id: number;
  client_id: number | null;
  user_id: number | null;
  /** Estado actual de la orden */
  status: 'draft' | 'requested' | 'prepared' | 'delivered';
  /** Total monetario de la orden */
  total: number | string;
  delivered_at: string | null;
  delivered_by: number | null;
  created_at: string | null;
  updated_at: string | null;
  items?: OrderItem[];
  items_count?: number;
}

export interface OrdersListPageProps extends PageProps, BaseModulePageProps {
  orders?: Paginated<Order>;
  filters?: Record<string, unknown>;
}

export interface OrderDetailPageProps extends PageProps, BaseModulePageProps {
  order: Order;
}
