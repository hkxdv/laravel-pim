import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import {
  ProductForm,
  type ProductFormData,
} from '@/pages/modules/inventory/components/product/product-form';
import { useProductForm } from '@/pages/modules/inventory/hooks/use-product-form';
import type { Product } from '@/pages/modules/inventory/types/product';
import type { User } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData, getUserName } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/react';
import type { FC } from 'react';
import type { ProductEditPageProps } from '../interfaces';

const ProductEditPage: FC = () => {
  const { product, auth, breadcrumbs } = usePage<PageProps & ProductEditPageProps>()
    .props as ProductEditPageProps & { auth: { user: unknown } };

  const user = extractUserData(auth.user as unknown as User);

  const computedBreadcrumbs =
    Array.isArray(breadcrumbs) && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.inventory.products.edit', 'Editar producto');

  const initialProduct: Product = product;
  const { formData, setFormData, handleSubmit, handleDelete, loading } =
    useProductForm(initialProduct);

  return (
    <AppLayout user={user} breadcrumbs={computedBreadcrumbs} contextualNavItems={[]}>
      <Head title="Editar producto" />
      <ModuleDashboardLayout
        title="Editar producto"
        description="Actualice los datos del producto seleccionado"
        userName={getUserName(user)}
        mainContent={
          <ProductForm
            data={formData as ProductFormData}
            processing={loading}
            setData={(field, value) => {
              setFormData((prev) => ({ ...prev, [field]: value as never }));
            }}
            onSubmit={(e) => {
              handleSubmit(e);
            }}
            onDelete={() => {
              handleDelete();
            }}
            isEditing
          />
        }
      />
    </AppLayout>
  );
};

export default ProductEditPage;
