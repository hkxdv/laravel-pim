/**
 * Página de Dashboard interno
 * Presenta módulos disponibles, estadísticas y navegación contextual.
 */
import {
  EnhancedStatsCards,
  type EnhancedStat,
} from '@/components/modules/module-enhanced-stats-cards';
import { ModuleNavCards } from '@/components/modules/module-nav-cards';
import { EnhancedStatsCardsSkeleton } from '@/components/modules/skeletons/module-enhanced-stats-cards-skeleton';
import { ModuleNavCardsSkeleton } from '@/components/modules/skeletons/module-nav-cards-skeleton';
import { RestrictedModulesSkeleton } from '@/components/modules/skeletons/restricted-modules-skeleton';
import { Skeleton } from '@/components/ui/skeleton';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import { useWelcomeToast } from '@/hooks/use-welcome-toast';
import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import type { BreadcrumbItem, ModuleNavItem, NavItemDefinition } from '@/types';
import { getLucideIcon, type IconName } from '@/utils/lucide-icons';
import { extractUserData } from '@/utils/user-data';
import { type PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/react';
import { LayoutDashboard } from 'lucide-react';
import { useMemo } from 'react';

/**
 * Información de un módulo mostrado en el dashboard.
 */
interface ModuleInfo {
  /** Nombre descriptivo del módulo */
  name: string;
  /** Descripción breve del módulo (opcional) */
  description?: string;
  /** URL completa al panel principal del módulo */
  href: string;
  /** Indica si el usuario actual tiene acceso al módulo */
  canAccess: boolean;
  /** Nombre del ícono a mostrar para el módulo (opcional) */
  icon?: string;
}

/**
 * Props para la página principal del dashboard.
 * Extiende las propiedades globales con datos específicos.
 */
interface DashboardPageProps extends PageProps {
  /** Lista de módulos disponibles en el sistema (compatibilidad previa) */
  modules: ModuleInfo[];
  /** Props separadas por el backend, opcionales por compatibilidad */
  accessibleModules?: ModuleInfo[];
  restrictedModules?: ModuleInfo[];
  /** Estadísticas del sistema proporcionadas por el backend (opcional) */
  systemStats?: EnhancedStat[];
  /** Título de la página proporcionado por el backend */
  pageTitle?: string;
  /** Descripción de la página proporcionada por el backend */
  description?: string;
  /** Ítems de navegación principal */
  mainNavItems?: NavItemDefinition[];
  /** Ítems de navegación de módulos */
  moduleNavItems?: NavItemDefinition[];
  /** Ítems de navegación global para la barra lateral */
  globalNavItems?: NavItemDefinition[];
}

/**
 * Página principal del panel de control.
 * Muestra los módulos disponibles según permisos del usuario y estadísticas generales.
 *
 * @returns Componente JSX con el panel de control principal
 */
export default function DashboardPage() {
  const {
    auth,
    modules,
    accessibleModules: accessibleModulesFromBackend,
    restrictedModules: restrictedModulesFromBackend,
    flash,
    mainNavItems,
    moduleNavItems,
    globalNavItems,
    systemStats,
    pageTitle,
    description,
  } = usePage<DashboardPageProps>().props;

  const isNavigating = useNavigationProgress({ delayMs: 150 });

  const userData = extractUserData(auth.user);

  useFlashToasts(flash);

  useWelcomeToast({
    userName: userData?.name ?? '',
    shouldShowToast: true,
    isMainDashboard: true,
  });

  // Usar exclusivamente breadcrumbs provenientes del backend (si existen)
  const { breadcrumbs } = usePage<DashboardPageProps & { breadcrumbs?: BreadcrumbItem[] }>().props;

  // Consumir únicamente las listas entregadas por el backend; si faltan, mostrar skeletons en UI
  const accessibleModules = useMemo(
    () => (Array.isArray(accessibleModulesFromBackend) ? accessibleModulesFromBackend : []),
    [accessibleModulesFromBackend],
  );
  const restrictedModules = useMemo(
    () => (Array.isArray(restrictedModulesFromBackend) ? restrictedModulesFromBackend : []),
    [restrictedModulesFromBackend],
  );

  // Convertir ModuleInfo a ModuleNavItem para los módulos accesibles
  const accessibleModuleNavItems: ModuleNavItem[] = accessibleModules.map((module) => ({
    name: module.name,
    description: module.description ?? '',
    href: module.href,
    icon: module.icon as IconName,
  }));

  // Usar únicamente navegación contextual si viene del backend
  const { contextualNavItems } = usePage<
    DashboardPageProps & { contextualNavItems?: NavItemDefinition[] }
  >().props;

  const statsSection = useMemo(
    () =>
      isNavigating || !systemStats ? (
        <EnhancedStatsCardsSkeleton />
      ) : (
        <div className="space-y-6">
          <EnhancedStatsCards stats={systemStats} />
        </div>
      ),
    [isNavigating, systemStats],
  );

  const mainContent = useMemo(
    () =>
      isNavigating || (!accessibleModulesFromBackend && !restrictedModulesFromBackend) ? (
        <div className="space-y-8">
          {/* Skeleton para Módulos Disponibles */}
          <div>
            <div className="mb-6 space-y-2">
              <Skeleton className="h-6 w-48 rounded-md" />
              <Skeleton className="h-4 w-72 rounded-md" />
            </div>
            <ModuleNavCardsSkeleton />
          </div>

          {/* Skeleton para Módulos Restringidos */}
          <div>
            <div className="mb-6 space-y-2">
              <Skeleton className="h-6 w-56 rounded-md" />
              <Skeleton className="h-4 w-96 rounded-md" />
            </div>
            <RestrictedModulesSkeleton />
          </div>
        </div>
      ) : (
        <>
          {accessibleModules.length > 0 && (
            <div className="mb-8">
              <div className="mb-6">
                <h2 className="text-foreground text-xl font-semibold">Módulos disponibles</h2>
                <p className="text-muted-foreground mt-1 text-sm">
                  Seleccione un módulo para acceder a sus funciones.
                </p>
              </div>
              <ModuleNavCards items={accessibleModuleNavItems} getIconComponent={getLucideIcon} />
            </div>
          )}

          {restrictedModules.length > 0 && (
            <div className="mb-8">
              <div className="mb-6">
                <h2 className="text-foreground text-xl font-semibold">Módulos restringidos</h2>
                <p className="text-muted-foreground mt-1 text-sm">
                  No tiene acceso a estos módulos.
                </p>
              </div>
              <div className="grid grid-cols-1 gap-5 opacity-70 sm:grid-cols-2 lg:grid-cols-3">
                {restrictedModules.map((module) => {
                  const IconComponent = getLucideIcon(module.icon);
                  return (
                    <div
                      key={module.name}
                      className="group border-border bg-muted/30 block h-full overflow-hidden rounded-lg border"
                    >
                      <div className="border-border flex flex-row items-center justify-between space-y-0 border-b p-4">
                        <h3 className="text-foreground text-xl font-semibold">{module.name}</h3>
                        <div className="bg-muted rounded-full p-2.5">
                          {IconComponent ? (
                            <IconComponent className="text-muted-foreground h-5 w-5" />
                          ) : (
                            <Skeleton className="h-5 w-5 rounded-md" />
                          )}
                        </div>
                      </div>
                      <div className="p-4">
                        {module.description ? (
                          <p className="text-muted-foreground text-sm">{module.description}</p>
                        ) : (
                          <Skeleton className="h-4 w-64 rounded-md" />
                        )}
                        <div className="mt-8 flex items-center justify-center space-x-2 rounded-md border border-yellow-200 bg-yellow-50 p-2 text-sm text-yellow-700 dark:border-yellow-700/50 dark:bg-yellow-900/30 dark:text-yellow-400">
                          <LayoutDashboard className="h-4 w-4 shrink-0" />
                          <span>Acceso restringido</span>
                        </div>
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}

          {modules.length === 0 && (
            <div className="mt-8 space-y-2">
              <Skeleton className="h-6 w-72 rounded-md" />
              <Skeleton className="h-4 w-96 rounded-md" />
            </div>
          )}
        </>
      ),
    [
      isNavigating,
      accessibleModules.length,
      accessibleModuleNavItems,
      restrictedModules,
      modules.length,
      accessibleModulesFromBackend,
      restrictedModulesFromBackend,
    ],
  );

  return (
    <AppLayout
      user={userData}
      breadcrumbs={breadcrumbs ?? []}
      mainNavItems={mainNavItems ?? []}
      moduleNavItems={moduleNavItems ?? []}
      contextualNavItems={contextualNavItems ?? []}
      globalNavItems={globalNavItems ?? []}
      pageTitle={pageTitle ?? ''}
      pageDescription={description ?? ''}
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
