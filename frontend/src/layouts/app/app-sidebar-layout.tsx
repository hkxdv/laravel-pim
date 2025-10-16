import { AppContent } from '@/components/app/app-content';
import { AppShell } from '@/components/app/app-shell';
import { AppSidebar } from '@/components/app/app-sidebar';
import { AppSidebarHeader } from '@/components/app/app-sidebar-header';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import { type BreadcrumbItem, type NavItemDefinition, type User } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { ShieldAlert } from 'lucide-react';
import { type ReactNode } from 'react';

interface AppSidebarLayoutProps {
  children: ReactNode;
  breadcrumbs?: BreadcrumbItem[];
  user: User | null;
  mainNavItems?: NavItemDefinition[];
  moduleNavItems?: NavItemDefinition[];
  contextualNavItems?: NavItemDefinition[];
  globalNavItems?: NavItemDefinition[];
  headerActions?: ReactNode;
}

export default function AppSidebarLayout({
  children,
  breadcrumbs = [],
  user,
  mainNavItems = [],
  moduleNavItems = [],
  contextualNavItems = [],
  globalNavItems = [],
  headerActions,
}: Readonly<AppSidebarLayoutProps>) {
  const { passwordChangeRequired } = usePage().props as { passwordChangeRequired?: boolean };
  return (
    <AppShell variant="sidebar">
      <AppSidebar
        user={user}
        mainNavItems={mainNavItems}
        moduleNavItems={moduleNavItems}
        contextualNavItems={contextualNavItems}
        globalNavItems={globalNavItems}
      />
      <AppContent variant="sidebar">
        <AppSidebarHeader breadcrumbs={breadcrumbs} headerActions={headerActions} />
        {passwordChangeRequired && (
          <Alert variant="destructive" className="mx-4 mt-4">
            <ShieldAlert />
            <AlertTitle>Cambio de contraseña requerido</AlertTitle>
            <AlertDescription>
              <p>
                Tu contraseña ha superado el tiempo máximo permitido por la política de seguridad.
                Actualízala para mantener el acceso sin restricciones.
              </p>
              <Button asChild variant="destructive" size="sm" className="mt-2">
                <Link href={route('internal.settings.password.edit')}>Actualizar contraseña</Link>
              </Button>
            </AlertDescription>
          </Alert>
        )}
        {children}
      </AppContent>
    </AppShell>
  );
}
