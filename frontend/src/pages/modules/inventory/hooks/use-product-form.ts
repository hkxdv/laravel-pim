import type { Product } from '@/pages/modules/inventory/types/product';
import { router } from '@inertiajs/react';
import { useState } from 'react';
import { route } from 'ziggy-js';

interface FormDataState {
  sku: string;
  name: string;
  brand: string;
  model: string;
  barcode: string;
  price: string; // backend envia como string decimal
  stock: number;
  is_active: boolean;
  metadata: string | null;
}

function safeParseJsonOrNull(input: string | null): Record<string, unknown> | null {
  if (!input) return null;
  try {
    const obj: unknown = JSON.parse(input);
    return obj && typeof obj === 'object' ? (obj as Record<string, unknown>) : null;
  } catch {
    return null;
  }
}

export function useProductForm(initialProduct?: Product) {
  const [formData, setFormData] = useState<FormDataState>({
    sku: initialProduct?.sku ?? '',
    name: initialProduct?.name ?? '',
    brand: initialProduct?.brand ?? '',
    model: initialProduct?.model ?? '',
    barcode: initialProduct?.barcode ?? '',
    price: initialProduct?.price ?? '0',
    stock: initialProduct?.stock ?? 0,
    is_active: initialProduct?.is_active ?? true,
    metadata: initialProduct?.metadata ? JSON.stringify(initialProduct.metadata) : null,
  });

  const [loading, setLoading] = useState<boolean>(false);

  const handleOnChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
    const { name, value } = e.target;
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleOnChangeText = (name: keyof FormDataState, value: string) => {
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleOnChangeBoolean = (name: keyof FormDataState, value: boolean) => {
    setFormData((prev) => ({ ...prev, [name]: value }));
  };

  const handleSubmit = (e: React.FormEvent<HTMLFormElement>) => {
    e.preventDefault();
    setLoading(true);

    try {
      const isEditing = Boolean(initialProduct?.id);
      const url = isEditing
        ? route('internal.inventory.products.update', initialProduct?.id)
        : route('internal.inventory.products.store');

      const safeMetadata = safeParseJsonOrNull(formData.metadata);

      const payload = {
        sku: formData.sku,
        name: formData.name,
        brand: formData.brand || null,
        model: formData.model || null,
        barcode: formData.barcode || null,
        price: formData.price,
        stock: formData.stock,
        is_active: formData.is_active,
        metadata: safeMetadata ? JSON.stringify(safeMetadata) : null,
      };

      const opts = {
        preserveScroll: true,
        onFinish: () => {
          setLoading(false);
        },
      } as const;
      const submit = isEditing ? router.put.bind(router) : router.post.bind(router);
      submit(url, payload, opts);
    } catch (error) {
      setLoading(false);
      throw error;
    }
  };

  const handleDelete = () => {
    if (!initialProduct?.id) return;
    setLoading(true);
    const url = route('internal.inventory.products.destroy', initialProduct.id);
    router.delete(url, {
      preserveScroll: true,
      onFinish: () => {
        setLoading(false);
      },
    });
  };

  return {
    formData,
    setFormData,
    loading,
    handleOnChange,
    handleOnChangeText,
    handleOnChangeBoolean,
    handleSubmit,
    handleDelete,
  };
}
