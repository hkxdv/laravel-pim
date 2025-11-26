import { buildStat } from '@/components/modules/helper/build-stat';
import {
  EnhancedStatsCards,
  type EnhancedStat,
} from '@/components/modules/module-enhanced-stats-cards';
import { ModuleIndexContent } from '@/components/modules/module-index-content';
import { ModuleIndexPage } from '@/components/modules/module-index-page';
import { EnhancedStatsCardsSkeleton } from '@/components/modules/skeletons/module-enhanced-stats-cards-skeleton';
import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import type { BreadcrumbItem } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { getLucideIcon, type IconName } from '@/utils/lucide-icons';
import { extractUserData } from '@/utils/user-data';
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { SalesIndexPageProps } from './interfaces';

/**
 * Componente del panel principal del Módulo de Ventas.
 */
export default function SalesIndexPanel() {
  const {
    auth,
    panelItems,
    contextualNavItems,
    pageTitle,
    description,
    stats,
    breadcrumbs,
    flash,
  } = usePage<SalesIndexPageProps>().props;

  const isNavigating = useNavigationProgress({ delayMs: 150 });

  const userData = extractUserData(auth.user);

  useFlashToasts(flash);

  // Usar breadcrumbs del backend con fallback
  const computedBreadcrumbs: BreadcrumbItem[] =
    breadcrumbs && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.sales.index', pageTitle ?? '');

  // Crear estadísticas  para el módulo a partir de los datos del backend
  const moduleStats: EnhancedStat[] = useMemo(() => {
    if (!stats) return [];
    const out: EnhancedStat[] = [];
    if (typeof stats.ordersTotal === 'number') {
      out.push(
        buildStat('Órdenes', stats.ordersTotal, 'Total de órdenes', 'ListOrdered' as IconName),
      );
    }
    if (typeof stats.deliveredOrders === 'number') {
      out.push(
        buildStat(
          'Entregadas',
          stats.deliveredOrders,
          'Órdenes entregadas',
          'CheckCircle2' as IconName,
        ),
      );
    }
    if (typeof stats.sumTotals === 'number') {
      out.push(
        buildStat('Ventas ($)', stats.sumTotals, 'Suma de totales', 'CircleDollarSign' as IconName),
      );
    }
    return out;
  }, [stats]);

  // Sección de estadísticas para el dashboard
  const statsSection =
    isNavigating || !stats ? (
      <EnhancedStatsCardsSkeleton />
    ) : (
      <EnhancedStatsCards stats={moduleStats} />
    );

  // Contenido principal para el dashboard
  const mainContent = useMemo(
    () => (
      <ModuleIndexContent
        isLoading={isNavigating || !panelItems}
        items={panelItems ?? []}
        getIconComponent={getLucideIcon}
        headerTitle="Secciones del Módulo"
        headerDescription="Acceda a las distintas secciones disponibles."
      />
    ),
    [isNavigating, panelItems],
  );

  return (
    <ModuleIndexPage
      user={userData}
      breadcrumbs={computedBreadcrumbs}
      contextualNavItems={contextualNavItems ?? []}
      pageTitle={pageTitle ?? ''}
      description={description ?? ''}
      staffUserName={userData?.name ?? ''}
      stats={statsSection}
      mainContent={mainContent}
      fullWidth={true}
    />
  );
}
