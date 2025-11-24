import RowActionsMenu from '@/components/data/row-actions-menu';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Product } from '@/pages/modules/inventory/types/product';
import { router } from '@inertiajs/react';
import { Pencil, PlusCircle, Trash } from 'lucide-react';
import { useCallback, useMemo, useState } from 'react';

interface ProductActionsCellProps {
  product: Product;
}

export function ProductActionsCell({ product }: Readonly<ProductActionsCellProps>) {
  const [isDeleting, setIsDeleting] = useState(false);
  const [isAdjusting, setIsAdjusting] = useState(false);
  const [openAdjust, setOpenAdjust] = useState(false);
  const [newStock, setNewStock] = useState<string>('');
  const currentStock = useMemo(
    () => (typeof product.stock === 'number' ? product.stock : 0),
    [product.stock],
  );

  const handleEdit = useCallback(() => {
    router.get(route('internal.inventory.products.edit', product.id));
  }, [product.id]);

  const handleDelete = useCallback(() => {
    if (!confirm(`¿Eliminar el producto "${product.name}"?`)) return;
    setIsDeleting(true);
    router.delete(route('internal.inventory.products.destroy', product.id), {
      preserveScroll: true,
      onFinish: () => {
        setIsDeleting(false);
      },
    });
  }, [product.id, product.name]);

  const openAdjustDialog = useCallback(() => {
    setNewStock(String(currentStock));
    setOpenAdjust(true);
  }, [currentStock]);

  const handleAdjust = useCallback(() => {
    const parsed = Number(newStock);
    if (!Number.isFinite(parsed) || parsed < 0) {
      alert('Ingrese un valor de stock válido (entero >= 0).');
      return;
    }
    setIsAdjusting(true);
    router.put(
      route('internal.inventory.products.update', product.id),
      { stock: parsed },
      {
        preserveScroll: true,
        onFinish: () => {
          setIsAdjusting(false);
          setOpenAdjust(false);
        },
      },
    );
  }, [newStock, product.id]);

  return (
    <div className="flex justify-end pr-4">
      <RowActionsMenu
        idToCopy={product.id}
        items={[
          {
            key: 'edit',
            label: 'Editar Producto',
            icon: <Pencil className="h-4 w-4" />,
            onClick: handleEdit,
          },
          {
            key: 'adjust_stock',
            label: 'Agregar stock',
            icon: <PlusCircle className="h-4 w-4" />,
            onClick: openAdjustDialog,
          },
          {
            key: 'delete',
            label: isDeleting ? 'Eliminando…' : 'Eliminar Producto',
            icon: <Trash className="h-4 w-4" />,
            variant: 'destructive',
            onClick: handleDelete,
            disabled: isDeleting,
          },
        ]}
      />

      <Dialog open={openAdjust} onOpenChange={setOpenAdjust}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Agregar stock</DialogTitle>
          </DialogHeader>
          <div className="space-y-2">
            <div className="text-sm">
              Producto: <span className="font-medium">{product.name}</span>
            </div>
            <div className="text-muted-foreground text-sm">Stock actual: {currentStock}</div>
            <div className="space-y-1">
              <Label htmlFor={`new-stock-${product.id}`}>Nuevo stock</Label>
              <Input
                id={`new-stock-${product.id}`}
                type="number"
                min={0}
                value={newStock}
                onChange={(e) => {
                  setNewStock(e.target.value);
                }}
                aria-label="Nuevo stock"
              />
            </div>
          </div>
          <DialogFooter>
            <Button
              variant="ghost"
              onClick={() => {
                setOpenAdjust(false);
              }}
              disabled={isAdjusting}
            >
              Cancelar
            </Button>
            <Button onClick={handleAdjust} disabled={isAdjusting}>
              {isAdjusting ? 'Guardando…' : 'Guardar'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </div>
  );
}
