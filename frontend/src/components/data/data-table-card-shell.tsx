import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/utils/cn';
import type { ReactNode } from 'react';

interface TableCardShellProps {
  title: ReactNode;
  totalBadge?: number | ReactNode;
  rightHeaderContent?: ReactNode;
  children: ReactNode;
  className?: string;
  contentClassName?: string;
}

/**
 * Contenedor de tarjeta para tablas (TableCardShell).
 * Proporciona un encabezado consistente con título, badge de totales y un área derecha para acciones (p. ej., buscador),
 * seguido por el cuerpo donde se renderiza la tabla.
 *
 * Props principales:
 * - title: Título del bloque/tabla.
 * - totalBadge?: Indicador/badge opcional con totales u otros datos.
 * - rightHeaderContent?: Zona de acciones en el encabezado (inputs de búsqueda, botones, etc.).
 * - children: Contenido de la tabla u otros elementos.
 *
 * Notas:
 * - Forma parte del flujo consolidado de tablas (useServerTable + TanStackDataTable + TableCardShell).
 * - Evita duplicar estructuras de Card en cada página.
 */
export default function TableCardShell({
  title,
  totalBadge,
  rightHeaderContent,
  children,
  className,
  contentClassName,
}: Readonly<TableCardShellProps>) {
  return (
    <Card className={cn('border-border shadow-sm', className)}>
      <CardHeader className="bg-card/50 pb-3">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-2">
            <CardTitle>{title}</CardTitle>
            {totalBadge !== undefined &&
              (typeof totalBadge === 'number' ? (
                <Badge variant="outline">{totalBadge} total</Badge>
              ) : (
                totalBadge
              ))}
          </div>
          {rightHeaderContent ? (
            <div className="flex items-center gap-2">{rightHeaderContent}</div>
          ) : null}
        </div>
      </CardHeader>
      <CardContent className={cn('p-6', contentClassName)}>{children}</CardContent>
    </Card>
  );
}
