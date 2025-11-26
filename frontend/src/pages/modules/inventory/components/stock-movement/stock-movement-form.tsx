import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { ProductAutocomplete } from '@/pages/modules/inventory/components/product/product-autocomplete';
import { usePage } from '@inertiajs/react';
import React from 'react';

export interface StockMovementFormData {
  product_id: number | null;
  product_search: string;
  type: 'in' | 'out' | 'adjust';
  quantity: number | null;
  new_stock: number | null;
  notes: string;
}

export interface StockMovementFormProps {
  data: StockMovementFormData;
  errors?: Partial<
    Record<
      keyof StockMovementFormData | 'product_id' | 'type' | 'quantity' | 'new_stock' | 'notes',
      string
    >
  >;
  processing?: boolean;
  setData: (field: keyof StockMovementFormData, value: string | number | null) => void;
  onSubmit: (e: React.FormEvent<HTMLFormElement>) => void;
}

function typeLabel(t: string): string {
  if (t === 'in') return 'Entrada';
  if (t === 'out') return 'Salida';
  return 'Ajuste';
}

export const StockMovementForm: React.FC<StockMovementFormProps> = ({
  data,
  errors,
  processing,
  setData,
  onSubmit,
}) => {
  const { errors: pageErrors } = usePage().props as { errors: Record<string, string> };

  return (
    <form onSubmit={onSubmit} className="space-y-6">
      <Card>
        <CardHeader>
          <CardTitle>Registrar movimiento de stock</CardTitle>
        </CardHeader>
        <CardContent className="grid grid-cols-1 gap-6 md:grid-cols-2">
          <div className="flex items-end gap-4 md:col-span-2">
            <div className="flex-1">
              <Label htmlFor="product_search">Producto</Label>
              <ProductAutocomplete
                value={data.product_search}
                onChange={(txt) => {
                  setData('product_search', txt);
                }}
                onSelect={(item) => {
                  const id = item.id == null ? null : Number(item.id);
                  setData('product_id', id);
                  // lock the search field to selected name for clarity
                  setData('product_search', item.name);
                }}
                placeholder="Buscar y seleccionar producto"
                className="w-full"
              />
              {(errors?.product_id ?? pageErrors['product_id']) && (
                <p className="mt-1 text-sm text-red-600">
                  {errors?.product_id ?? pageErrors['product_id']}
                </p>
              )}
            </div>
          </div>

          <div>
            <Label htmlFor="type">Tipo de movimiento</Label>
            <select
              id="type"
              className="border-input bg-background text-foreground ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex h-9 w-full rounded-md border px-3 py-1 text-sm shadow-sm focus-visible:ring-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
              value={data.type}
              onChange={(e) => {
                setData('type', e.target.value);
              }}
            >
              <option value="in">Entrada</option>
              <option value="out">Salida</option>
              <option value="adjust">Ajuste</option>
            </select>
            {(errors?.type ?? pageErrors['type']) && (
              <p className="mt-1 text-sm text-red-600">{errors?.type ?? pageErrors['type']}</p>
            )}
          </div>

          {data.type === 'adjust' ? (
            <div>
              <Label htmlFor="new_stock">Nuevo stock</Label>
              <Input
                id="new_stock"
                type="number"
                value={data.new_stock ?? 0}
                onChange={(e) => {
                  setData('new_stock', Number(e.target.value));
                }}
                placeholder="Cantidad exacta"
              />
              {(errors?.new_stock ?? pageErrors['new_stock']) && (
                <p className="mt-1 text-sm text-red-600">
                  {errors?.new_stock ?? pageErrors['new_stock']}
                </p>
              )}
            </div>
          ) : (
            <div>
              <Label htmlFor="quantity">Cantidad</Label>
              <Input
                id="quantity"
                type="number"
                value={data.quantity ?? 0}
                onChange={(e) => {
                  setData('quantity', Number(e.target.value));
                }}
                placeholder="Unidades"
              />
              {(errors?.quantity ?? pageErrors['quantity']) && (
                <p className="mt-1 text-sm text-red-600">
                  {errors?.quantity ?? pageErrors['quantity']}
                </p>
              )}
            </div>
          )}

          <div className="md:col-span-2">
            <Label htmlFor="notes">Notas</Label>
            <Textarea
              id="notes"
              value={data.notes}
              onChange={(e) => {
                setData('notes', e.target.value);
              }}
              placeholder={`Detalles del ${typeLabel(data.type)} (opcional)`}
            />
            {(errors?.notes ?? pageErrors['notes']) && (
              <p className="mt-1 text-sm text-red-600">{errors?.notes ?? pageErrors['notes']}</p>
            )}
          </div>
        </CardContent>
      </Card>

      <div className="flex items-center gap-3">
        <Button type="submit" disabled={processing}>
          Registrar movimiento
        </Button>
      </div>
    </form>
  );
};
