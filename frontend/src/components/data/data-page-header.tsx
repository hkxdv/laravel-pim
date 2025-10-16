import { type ReactNode } from 'react';

interface PageHeaderProps {
  /** Título principal de la página */
  title: string;
  /** Descripción opcional */
  description?: string;
  /** Acciones a mostrar en la parte derecha (botones, etc.) */
  actions?: ReactNode;
  /** Clase CSS adicional para el contenedor */
  className?: string;
  /** Icono opcional para mostrar junto al título */
  icon?: ReactNode;
}

/**
 * Encabezado de página (PageHeader).
 * Muestra título, descripción breve y acciones contextuales (por ejemplo, botones primarios/ secundarios).
 *
 * Props típicas:
 * - title: Título principal de la página.
 * - description?: Texto breve explicativo.
 * - actions?: Nodo de acciones (botones, menús, etc.).
 *
 * Notas:
 * - Mantén consistencia con los títulos de las tarjetas/ tablas para una UX coherente.
 */
export function PageHeader({
  title,
  description,
  actions,
  className = '',
  icon,
}: Readonly<PageHeaderProps>) {
  return (
    <div className={`mb-6 flex flex-wrap items-center justify-between gap-4 ${className}`}>
      <div className="flex items-center gap-2">
        {icon && <span className="text-primary">{icon}</span>}
        <div>
          <h1 className="text-foreground text-2xl font-semibold">{title}</h1>
          {description && <p className="text-muted-foreground mt-1 text-sm">{description}</p>}
        </div>
      </div>

      {actions && <div className="flex flex-wrap gap-2">{actions}</div>}
    </div>
  );
}
