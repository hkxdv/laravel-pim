import type { BaseModulePageProps } from '@/types';
import type { PageProps } from '@inertiajs/core';

/**
 * Propiedades para la página del panel principal del Módulo WhatsApp.
 * Extiende las propiedades globales de página con datos específicos del módulo.
 */
export interface WhatsAppIndexPageProps extends PageProps, BaseModulePageProps<object> {}
