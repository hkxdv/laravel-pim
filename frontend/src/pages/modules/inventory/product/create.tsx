import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import {
  ProductForm,
  type ProductFormData,
} from '@/pages/modules/inventory/components/product/product-form';
import { useProductForm } from '@/pages/modules/inventory/hooks/use-product-form';
import type { User } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData, getUserName } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/react';
import type { FC } from 'react';
import type { ProductCreatePageProps } from '../interfaces';

const ProductCreatePage: FC = () => {
  const { auth, breadcrumbs } = usePage<PageProps & ProductCreatePageProps>()
    .props as ProductCreatePageProps & { auth: { user: unknown } };

  const user = extractUserData(auth.user as unknown as User);

  const computedBreadcrumbs =
    breadcrumbs && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.inventory.products.create', 'Crear producto');

  const { formData, setFormData, handleSubmit, loading } = useProductForm();

  return (
    <AppLayout user={user} breadcrumbs={computedBreadcrumbs} contextualNavItems={[]}>
      <Head title="Crear producto" />
      <ModuleDashboardLayout
        title="Crear producto"
        description="Complete los datos del producto y guárdelos"
        userName={getUserName(user)}
        mainContent={
          <ProductForm
            data={formData as ProductFormData}
            processing={loading}
            setData={(field, value) => {
              setFormData((prev) => ({ ...prev, [field]: value as never }));
            }}
            onSubmit={handleSubmit}
            isEditing={false}
          />
        }
      />
    </AppLayout>
  );
};

export default ProductCreatePage;
