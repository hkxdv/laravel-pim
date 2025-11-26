import { useFlashToasts } from '@/hooks/use-flash-toasts';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import {
  StockMovementForm,
  type StockMovementFormData,
} from '@/pages/modules/inventory/components/stock-movement/stock-movement-form';
import { useStockMovementForm } from '@/pages/modules/inventory/hooks/use-stock-movement-form';
import type { User } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData, getUserName } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/react';
import type { FC } from 'react';
import type { StockMovementCreatePageProps } from '../interfaces';

const StockMovementCreatePage: FC = () => {
  const { auth, breadcrumbs, flash } = usePage<PageProps & StockMovementCreatePageProps>()
    .props as StockMovementCreatePageProps & { auth: { user: unknown } };

  const user = extractUserData(auth.user as unknown as User);

  const computedBreadcrumbs =
    breadcrumbs && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.inventory.stock_movements.create', 'Registrar movimiento');

  const { formData, setFormData, handleSubmit, loading, clientErrors } = useStockMovementForm();

  useFlashToasts(
    flash
      ? {
          success: flash.success ?? '',
          error: flash.error ?? '',
          info: flash.info ?? '',
          warning: flash.warning ?? '',
        }
      : undefined,
  );

  return (
    <AppLayout user={user} breadcrumbs={computedBreadcrumbs} contextualNavItems={[]}>
      <Head title="Registrar movimiento" />
      <ModuleDashboardLayout
        title="Registrar movimiento"
        description="Seleccione un producto y complete los datos"
        userName={getUserName(user)}
        mainContent={
          <StockMovementForm
            data={formData as StockMovementFormData}
            errors={clientErrors}
            processing={loading}
            setData={(field, value) => {
              setFormData((prev) => ({ ...prev, [field]: value as never }));
            }}
            onSubmit={handleSubmit}
          />
        }
      />
    </AppLayout>
  );
};

export default StockMovementCreatePage;
