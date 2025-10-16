/**
 * Página principal del Módulo de Administración.
 * Muestra tarjetas de navegación y estadísticas del sistema.
 */
import { buildStat } from '@/components/modules/helper/build-stat';
import { EnhancedStatsCards } from '@/components/modules/module-enhanced-stats-cards';
import { ModuleNavCards } from '@/components/modules/module-nav-cards';
import { EnhancedStatsCardsSkeleton } from '@/components/modules/skeletons/module-enhanced-stats-cards-skeleton';
import { ModuleNavCardsSkeleton } from '@/components/modules/skeletons/module-nav-cards-skeleton';
import { Skeleton } from '@/components/ui/skeleton';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import type { BreadcrumbItem } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { getLucideIcon, type IconName } from '@/utils/lucide-icons';
import { extractUserData } from '@/utils/user-data';
import { Head, usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import { type AdminIndexPageProps } from './interfaces';

/**
 * Página principal del Módulo de Administración.
 *
 * @returns Elemento JSX del panel principal de administración.
 */
export default function AdminIndexPage() {
  const {
    auth,
    flash,
    panelItems,
    stats,
    mainNavItems,
    moduleNavItems,
    contextualNavItems,
    globalNavItems,
    breadcrumbs,
    pageTitle,
    description,
  } = usePage<AdminIndexPageProps>().props as AdminIndexPageProps;

  const isNavigating = useNavigationProgress({ delayMs: 150 });

  const userData = extractUserData(auth.user);

  useFlashToasts(flash);

  // Usar breadcrumbs del backend con fallback a `createBreadcrumbs`
  const computedBreadcrumbs: BreadcrumbItem[] =
    breadcrumbs && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.admin.panel', pageTitle ?? '');

  // Sección de estadísticas
  const statsSection = useMemo(
    () =>
      isNavigating || !stats ? (
        <EnhancedStatsCardsSkeleton />
      ) : (
        <EnhancedStatsCards
          stats={[
            buildStat(
              'Usuarios Totales',
              stats.totalUsers,
              'Total de usuarios registrados.',
              'Users' as IconName,
            ),
            buildStat(
              'Roles Totales',
              stats.totalRoles,
              'Roles definidos en el sistema.',
              'ShieldCheck' as IconName,
            ),
            buildStat(
              'Actividad Reciente',
              stats.recentActivityCount,
              'Acciones en la última semana.',
              'Activity' as IconName,
            ),
          ]}
        />
      ),
    [isNavigating, stats],
  );

  // Contenido principal
  const mainContent = useMemo(
    () =>
      isNavigating || !panelItems ? (
        <div>
          <div className="mb-6 space-y-2">
            <Skeleton className="h-6 w-48 rounded-md" />
            <Skeleton className="h-4 w-72 rounded-md" />
          </div>
          <ModuleNavCardsSkeleton />
        </div>
      ) : (
        <div>
          <div className="mb-6">
            <h2 className="text-xl font-semibold text-gray-800 dark:text-white">
              Secciones del Módulo
            </h2>
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
              Seleccione una opción para gestionar el sistema.
            </p>
          </div>
          <ModuleNavCards items={panelItems} getIconComponent={getLucideIcon} />
        </div>
      ),
    [isNavigating, panelItems],
  );

  return (
    <AppLayout
      user={userData}
      breadcrumbs={computedBreadcrumbs}
      mainNavItems={mainNavItems ?? []}
      moduleNavItems={moduleNavItems ?? []}
      contextualNavItems={contextualNavItems ?? []}
      globalNavItems={globalNavItems ?? []}
      pageTitle={pageTitle ?? ''}
    >
      <Head title={pageTitle ?? ''} />

      <ModuleDashboardLayout
        title={pageTitle ?? ''}
        description={description ?? ''}
        userName={userData?.name ?? ''}
        stats={statsSection}
        mainContent={mainContent}
        fullWidth={true}
      />
    </AppLayout>
  );
}
