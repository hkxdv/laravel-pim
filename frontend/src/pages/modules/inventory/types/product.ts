import type { BaseEntity } from '@/types';

/**
 * Tipo para un Producto en el sistema de inventario.
 * Basado en el modelo de backend `Modules\Inventory\Models\Product` y el servicio de inventario.
 */
export interface Product extends BaseEntity {
  sku: string;
  name: string;
  brand: string | null;
  model: string | null;
  barcode: string | null;
  /** Precio casteado como decimal en backend; se serializa como string. */
  price: string;
  stock: number;
  is_active: boolean;
  /** Campo libre para datos adicionales (JSON). */
  metadata?: Record<string, unknown> | null;
}
