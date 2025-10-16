import type { BaseModulePageProps } from '@/types';
import type { PageProps } from '@inertiajs/core';

/**
 * Propiedades para la página del panel principal del Módulo 02.
 * Extiende las propiedades globales de página con datos específicos del módulo.
 *
 * Nota: `stats` contiene contadores agregados entregados por el backend y
 * usados para componer `EnhancedStatsCards` en la UI.
 */
export interface Module02IndexPageProps
  extends PageProps,
    BaseModulePageProps<{
      // Props de ej. para las stats del módulo
      /** Número total de solicitudes registradas */
      totalRequests?: number;
    }> {}
