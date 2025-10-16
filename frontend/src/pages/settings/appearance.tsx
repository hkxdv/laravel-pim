import AppearanceTabs from '@/components/appearance/appearance-tabs';
import HeadingSmall from '@/components/heading-small';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem, NavItemDefinition } from '@/types';
import { extractUserData } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/react';
import { useEffect } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Configuración de apariencia',
    href: '/settings/appearance',
  },
];

// Define una interfaz para las props específicas de esta página
interface AppearancePageProps extends PageProps {
  contextualNavItems?: NavItemDefinition[];
}

export default function AppearancePage() {
  // Obtener contextualNavItems de las props de la página
  const { auth, contextualNavItems, flash } = usePage<AppearancePageProps>().props;

  // Hook de notificaciones
  const { showSuccess, showError } = useToastNotifications();

  // Procesar mensajes flash del backend
  useEffect(() => {
    if (flash.success) {
      showSuccess(flash.success);
    }
    if (flash.error) {
      showError(flash.error);
    }
  }, [flash, showSuccess, showError]);

  return (
    <AppLayout
      user={extractUserData(auth.user)}
      breadcrumbs={breadcrumbs}
      contextualNavItems={contextualNavItems ?? []}
    >
      <Head title="Configuración de apariencia" />

      <SettingsLayout>
        <div className="space-y-8">
          <HeadingSmall
            title="Configuración de apariencia"
            description="Actualiza la configuración de apariencia de tu cuenta"
          />
          <div className="max-w-[336px]">
            <AppearanceTabs />
          </div>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
