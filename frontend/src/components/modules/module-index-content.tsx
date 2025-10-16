import { ModuleEmptyState } from '@/components/modules/module-empty-state';
import { ModuleNavCards } from '@/components/modules/module-nav-cards';
import { ModuleNavCardsSkeleton } from '@/components/modules/skeletons/module-nav-cards-skeleton';
import { Skeleton } from '@/components/ui/skeleton';
import type { ModuleNavItem } from '@/types';
import type { IconName } from '@/utils/lucide-icons';
import type { LucideIcon } from 'lucide-react';

export interface ModuleIndexContentProps {
  isLoading: boolean;
  items?: ModuleNavItem[] | null;
  getIconComponent: (iconName?: string | LucideIcon | null) => LucideIcon | null;
  headerTitle?: string;
  headerDescription?: string;
  emptyStateMessage?: string;
  emptyStateIcon?: IconName;
}

/**
 * Componente genérico para renderizar el contenido principal de un índice de módulo.
 * Muestra skeletons durante navegación/carga, tarjetas de navegación si hay items,
 * o un estado vacío en caso contrario.
 */
export function ModuleIndexContent({
  isLoading,
  items,
  getIconComponent,
  headerTitle = 'Secciones del Módulo',
  headerDescription,
  emptyStateMessage = 'No hay secciones disponibles.',
  emptyStateIcon = 'LayoutDashboard',
}: Readonly<ModuleIndexContentProps>) {
  if (isLoading || !items) {
    return (
      <div>
        <div className="mb-6 space-y-2">
          <Skeleton className="h-6 w-48 rounded-md" />
          <Skeleton className="h-4 w-72 rounded-md" />
        </div>
        <ModuleNavCardsSkeleton />
      </div>
    );
  }

  if (items.length > 0) {
    return (
      <div>
        <div className="mb-6">
          <h2 className="text-xl font-semibold text-gray-800 dark:text-white">{headerTitle}</h2>
          {headerDescription && (
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">{headerDescription}</p>
          )}
        </div>
        <ModuleNavCards items={items} getIconComponent={getIconComponent} />
      </div>
    );
  }

  return <ModuleEmptyState message={emptyStateMessage} icon={emptyStateIcon} />;
}
