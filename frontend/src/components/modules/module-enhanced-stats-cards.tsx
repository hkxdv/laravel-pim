'use client';

import { getLucideIcon } from '@/utils/lucide-icons';
import { QuestionMarkIcon } from '@radix-ui/react-icons';

/**
 * Define la estructura de una tarjeta de estadística mejorada.
 */
export interface EnhancedStat {
  /** Título principal de la tarjeta (e.g., "Ingresos Totales") */
  title: string;
  /** Valor numérico o de texto a mostrar (e.g., "$45,231.89") */
  value: string | number;
  /** Descripción o contexto adicional (e.g., "+20.1% desde el mes pasado") */
  description: string;
  /** Nombre del ícono de Lucide a mostrar en la tarjeta */
  icon: string;
}

/**
 * Componente para renderizar una cuadrícula de tarjetas de estadísticas mejoradas.
 * Cada tarjeta muestra un título, un valor, una descripción y un ícono.
 *
 * @param stats - Un array de objetos `EnhancedStat` para mostrar.
 * @returns Un componente JSX que renderiza las tarjetas de estadísticas.
 */
export function EnhancedStatsCards({ stats }: Readonly<{ stats: EnhancedStat[] }>) {
  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
      {stats.map((stat) => {
        const IconComponent = getLucideIcon(stat.icon) ?? QuestionMarkIcon;

        return (
          <div
            key={stat.title}
            className="border-border bg-card overflow-hidden rounded-lg border shadow-sm"
          >
            <div className="flex items-center p-5">
              <div className="border-border bg-muted flex h-12 w-12 shrink-0 items-center justify-center rounded-full border">
                <IconComponent
                  className="text-muted-foreground h-6 w-6"
                  aria-hidden="true"
                  strokeWidth={2}
                />
              </div>
              <div className="ml-5 w-0 flex-1">
                <dl>
                  <dt className="text-muted-foreground truncate text-sm font-medium">
                    {stat.title}
                  </dt>
                  <dd>
                    <div className="text-foreground text-2xl font-bold">{stat.value}</div>
                    <p className="text-muted-foreground mt-1 text-xs">{stat.description}</p>
                  </dd>
                </dl>
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );
}
