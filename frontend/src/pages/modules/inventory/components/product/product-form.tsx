import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Switch } from '@/components/ui/switch';
import DynamicSpecForm from '@/pages/modules/inventory/components/product/dynamic-spec-form';
import type { Product } from '@/pages/modules/inventory/types/product';
import { usePage } from '@inertiajs/react';
import React from 'react';

export interface ProductFormData {
  sku: string;
  name: string;
  brand: string;
  model: string;
  barcode: string;
  price: string;
  stock: number;
  is_active: boolean;
  metadata?: string | null;
}

export interface ProductFormProps {
  data: ProductFormData;
  errors?: Partial<Record<keyof ProductFormData, string>>;
  processing?: boolean;
  setData: (field: keyof ProductFormData, value: string | number | boolean | null) => void;
  onSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
  onDelete?: () => void;
  isEditing?: boolean;
  product?: Product;
}

export const ProductForm: React.FC<ProductFormProps> = ({
  data,
  errors,
  processing,
  setData,
  onSubmit,
  onDelete,
  isEditing = false,
}) => {
  const { errors: pageErrors } = usePage().props as { errors: Record<string, string> };

  return (
    <form onSubmit={onSubmit} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>{isEditing ? 'Editar producto' : 'Crear producto'}</CardTitle>
        </CardHeader>
        <CardContent className="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div>
            <Label htmlFor="sku">SKU</Label>
            <Input
              id="sku"
              value={data.sku}
              onChange={(e) => {
                setData('sku', e.target.value);
              }}
              placeholder="SKU único del producto"
            />
            {errors?.sku && <p className="mt-1 text-sm text-red-600">{errors.sku}</p>}
          </div>

          <div>
            <Label htmlFor="name">Nombre</Label>
            <Input
              id="name"
              value={data.name}
              onChange={(e) => {
                setData('name', e.target.value);
              }}
              placeholder="Nombre descriptivo"
            />
            {errors?.name && <p className="mt-1 text-sm text-red-600">{errors.name}</p>}
          </div>

          <div>
            <Label htmlFor="brand">Marca</Label>
            <Input
              id="brand"
              value={data.brand}
              onChange={(e) => {
                setData('brand', e.target.value);
              }}
              placeholder="Marca del producto"
            />
            {errors?.brand && <p className="mt-1 text-sm text-red-600">{errors.brand}</p>}
          </div>

          <div>
            <Label htmlFor="model">Modelo</Label>
            <Input
              id="model"
              value={data.model}
              onChange={(e) => {
                setData('model', e.target.value);
              }}
              placeholder="Modelo o versión"
            />
            {errors?.model && <p className="mt-1 text-sm text-red-600">{errors.model}</p>}
          </div>

          <div>
            <Label htmlFor="barcode">Código de barras</Label>
            <Input
              id="barcode"
              value={data.barcode}
              onChange={(e) => {
                setData('barcode', e.target.value);
              }}
              placeholder="EAN/UPC u otro"
            />
            {errors?.barcode && <p className="mt-1 text-sm text-red-600">{errors.barcode}</p>}
          </div>

          <div>
            <Label htmlFor="price">Precio</Label>
            <Input
              id="price"
              type="number"
              step="0.01"
              value={data.price}
              onChange={(e) => {
                setData('price', e.target.value);
              }}
              placeholder="Precio unitario"
            />
            {errors?.price && <p className="mt-1 text-sm text-red-600">{errors.price}</p>}
          </div>

          <div>
            <Label htmlFor="stock">Stock</Label>
            <Input
              id="stock"
              type="number"
              value={data.stock}
              onChange={(e) => {
                setData('stock', Number(e.target.value));
              }}
              placeholder="Unidades disponibles"
            />
            {errors?.stock && <p className="mt-1 text-sm text-red-600">{errors.stock}</p>}
          </div>

          <div className="flex items-center space-x-3">
            <Switch
              id="is_active"
              checked={data.is_active}
              onCheckedChange={(checked) => {
                setData('is_active', checked);
              }}
            />
            <Label htmlFor="is_active">Activo</Label>
            {errors?.is_active && <p className="mt-1 text-sm text-red-600">{errors.is_active}</p>}
          </div>

          <div className="md:col-span-2">
            <Label htmlFor="metadata">Metadatos (JSON opcional)</Label>
            <Input
              id="metadata"
              value={data.metadata ?? ''}
              onChange={(e) => {
                setData('metadata', e.target.value);
              }}
              placeholder='{"color":"rojo","peso":"1kg"}'
            />
          </div>
        </CardContent>
      </Card>

      <DynamicSpecForm
        metadataJson={data.metadata ?? null}
        onMetadataChange={(json) => {
          setData('metadata', json);
        }}
        errors={pageErrors}
      />

      <div className="flex items-center gap-3">
        <Button type="submit" disabled={processing}>
          {isEditing ? 'Guardar cambios' : 'Crear producto'}
        </Button>
        {isEditing && onDelete && (
          <Button type="button" variant="destructive" onClick={onDelete}>
            Eliminar producto
          </Button>
        )}
      </div>
    </form>
  );
};
