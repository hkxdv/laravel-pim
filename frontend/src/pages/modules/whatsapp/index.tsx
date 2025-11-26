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
import { getLucideIcon } from '@/utils/lucide-icons';
import { extractUserData } from '@/utils/user-data';
import { usePage } from '@inertiajs/react';
import { useMemo } from 'react';
import type { WhatsAppIndexPageProps } from './interfaces';

export default function WhatsAppIndexPanel() {
  const {
    auth,
    panelItems,
    contextualNavItems,
    pageTitle,
    description,
    stats,
    breadcrumbs,
    flash,
  } = usePage<WhatsAppIndexPageProps>().props;

  const isNavigating = useNavigationProgress({ delayMs: 150 });

  const userData = extractUserData(auth.user);

  useFlashToasts(flash);

  const computedBreadcrumbs: BreadcrumbItem[] =
    breadcrumbs && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.whatsapp.index', pageTitle ?? '');

  // eslint-disable-next-line sonarjs/no-all-duplicated-branches
  const moduleStats: EnhancedStat[] = useMemo(() => (stats ? [] : []), [stats]);

  const statsSection =
    isNavigating || !stats ? (
      <EnhancedStatsCardsSkeleton />
    ) : (
      <EnhancedStatsCards stats={moduleStats} />
    );

  const mainContent = useMemo(
    () => (
      <ModuleIndexContent
        isLoading={isNavigating || !panelItems}
        items={panelItems ?? []}
        getIconComponent={getLucideIcon}
        headerTitle="Secciones del Módulo"
        headerDescription="Explore las funcionalidades del Módulo WhatsApp."
        emptyStateMessage="No hay secciones disponibles en el módulo por el momento."
        emptyStateIcon="LayoutDashboard"
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
