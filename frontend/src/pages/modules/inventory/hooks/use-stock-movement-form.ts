import { useToastNotifications } from '@/hooks/use-toast-notifications';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { route } from 'ziggy-js';

export type StockMovementType = 'in' | 'out' | 'adjust';

export interface StockMovementFormData {
  product_id: number | null;
  product_search: string;
  type: StockMovementType;
  quantity: number | null;
  new_stock: number | null;
  notes: string;
}

export function useStockMovementForm() {
  const [formData, setFormData] = useState<StockMovementFormData>({
    product_id: null,
    product_search: '',
    type: 'in',
    quantity: 1,
    new_stock: null,
    notes: '',
  });

  const [loading, setLoading] = useState(false);
  const [clientErrors, setClientErrors] = useState<Record<string, string>>({});
  const { showFieldError } = useToastNotifications();

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);

    // Validación ligera del cliente antes de enviar
    const nextErrors: Record<string, string> = {};
    if (formData.product_id == null) {
      nextErrors['product_id'] = 'Selecciona un producto.';
      showFieldError('product_id', nextErrors['product_id']);
    }

    if (formData.type === 'adjust') {
      if (formData.new_stock == null || formData.new_stock < 0) {
        nextErrors['new_stock'] = 'El nuevo stock debe ser 0 o mayor.';
        showFieldError('new_stock', nextErrors['new_stock']);
      }
    } else {
      if (formData.quantity == null || formData.quantity <= 0) {
        nextErrors['quantity'] = 'La cantidad debe ser mayor a 0.';
        showFieldError('quantity', nextErrors['quantity']);
      }
    }

    if (Object.keys(nextErrors).length > 0) {
      setClientErrors(nextErrors);
      setLoading(false);
      return;
    } else {
      setClientErrors({});
    }

    const payload: Record<string, string | number | null> = {
      product_id: formData.product_id,
      type: formData.type,
      notes: formData.notes || null,
    };

    if (formData.type === 'adjust') {
      payload['new_stock'] = formData.new_stock ?? 0;
      payload['quantity'] = null;
    } else {
      payload['quantity'] = formData.quantity ?? 0;
      payload['new_stock'] = null;
    }

    router.post(route('internal.inventory.stock_movements.store'), payload, {
      preserveScroll: true,
      onSuccess: () => {
        // Reset básico del formulario tras éxito
        setFormData({
          product_id: null,
          product_search: '',
          type: 'in',
          quantity: 1,
          new_stock: null,
          notes: '',
        });
      },
      onError: () => {
        // Mantener loading coherente
        setLoading(false);
      },
      onFinish: () => {
        setLoading(false);
      },
    });
  };

  return { formData, setFormData, handleSubmit, loading, clientErrors };
}
