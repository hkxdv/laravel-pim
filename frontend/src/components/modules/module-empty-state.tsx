import { Card, CardContent } from '@/components/ui/card';
import { getLucideIcon } from '@/utils/lucide-icons';
import { LayoutDashboard } from 'lucide-react';
import { type ModuleEmptyStateProps } from './interfaces';

/**
 * Componente para mostrar un estado vacío cuando no hay elementos disponibles.
 */
export function ModuleEmptyState({
  message = 'No hay elementos disponibles',
  icon = 'LayoutDashboard',
  IconComponent,
}: Readonly<ModuleEmptyStateProps>) {
  // Usar el componente de icono proporcionado o intentar obtenerlo por nombre
  const Icon = IconComponent ?? getLucideIcon(icon) ?? LayoutDashboard;

  return (
    <Card className="border-muted-foreground border border-dashed">
      <CardContent className="flex flex-col items-center justify-center p-6">
        <Icon className="text-muted-foreground mb-4 h-12 w-12 opacity-50" />
        <p className="text-center text-gray-500 dark:text-gray-400">{message}</p>
      </CardContent>
    </Card>
  );
}
