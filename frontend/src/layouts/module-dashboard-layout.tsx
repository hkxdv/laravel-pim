'use client';

import { ModuleHeader } from '@/components/modules/module-header';
import { type ReactNode } from 'react';

interface DashboardLayoutProps {
  title: string;
  description?: string;
  userName: string;
  stats?: ReactNode;
  actions?: ReactNode;
  mainContent: ReactNode;
  sideContent?: ReactNode;
  fullWidth?: boolean;
  showGreeting?: boolean;
}

/**
 * Componente de layout para dashboards que estandariza la estructura de todos los paneles.
 * Proporciona secciones para encabezado, estadísticas, contenido principal y contenido lateral.
 */
export function ModuleDashboardLayout({
  title,
  description,
  userName,
  stats,
  actions,
  mainContent,
  sideContent,
  showGreeting = true,
  fullWidth = true,
}: Readonly<DashboardLayoutProps>) {
  return (
    <div className="container mx-auto p-4 sm:p-6 lg:p-8">
      <div className="flex flex-col gap-6">
        {/* Header con acciones opcionales */}
        <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
          {title && (
            <ModuleHeader
              title={title}
              description={description ?? ''}
              userName={userName}
              showGreeting={showGreeting}
            />
          )}
          {actions && <div className="flex justify-end">{actions}</div>}
        </div>

        {/* Estadísticas */}
        {stats && <div className="w-full">{stats}</div>}

        {/* Contenido principal y lateral */}
        {sideContent && !fullWidth ? (
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div className="lg:col-span-2">{mainContent}</div>
            <div className="lg:col-span-1">{sideContent}</div>
          </div>
        ) : (
          <div>{mainContent}</div>
        )}
      </div>
    </div>
  );
}
