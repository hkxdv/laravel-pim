import type { BaseModulePageProps } from '@/types';
import type { PageProps } from '@inertiajs/core';

/**
 * Propiedades para la página del panel principal del Módulo 01.
 * Extiende las propiedades globales de página con datos específicos del módulo.
 *
 * Nota: `stats` es un objeto tipado por el backend y usado para construir
 * tarjetas de estadísticas en el frontend. Mantener nombres de campos estables.
 */
export interface Module01IndexPageProps
  extends PageProps,
    BaseModulePageProps<{
      // Props de ej. para las stats del módulo
      /** Número total de registros del día actual */
      totalRegistersToday: number;
    }> {}
