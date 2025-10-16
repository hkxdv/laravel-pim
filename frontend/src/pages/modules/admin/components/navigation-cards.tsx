import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { ArrowRight, Users, type LucideIcon } from 'lucide-react'; // Users como fallback si no hay panelItems
import React from 'react';

/**
 * Define la estructura de un ítem del panel para navegación.
 * Reutiliza la interfaz de AdminIndexPage para consistencia.
 */
interface PanelItem {
  name: string;
  description: string;
  route_name: string;
  icon?: string;
  permission?: string; // Aunque no se use para filtrar aquí, se mantiene por consistencia si viene del backend.
}

/**
 * Props para el componente {@link AdminFeatureNavigationCards}.
 */
interface AdminFeatureNavigationCardsProps {
  /** Array de ítems de panel a mostrar como tarjetas de navegación. */
  panelItems: PanelItem[];
  /**
   * Función que mapea un nombre de ícono (string) a su respectivo componente de Lucide React.
   * @param iconName - El nombre del ícono.
   * @returns El componente de ícono de Lucide React correspondiente.
   */
  getIconComponent: (iconName?: string | null) => LucideIcon | null;
}

/**
 * Componente para renderizar las tarjetas de navegación a las diferentes funcionalidades
 * del Módulo de Administración.
 *
 * @param props - Las propiedades del componente, incluyendo los `panelItems` y `getIconComponent`.
 * @returns El elemento JSX que representa las tarjetas de navegación de funcionalidades.
 */
const AdminFeatureNavigationCards: React.FC<AdminFeatureNavigationCardsProps> = ({
  panelItems,
  getIconComponent,
}) => {
  if (panelItems.length === 0) {
    return (
      <Card className="border-dashed">
        <CardContent className="flex flex-col items-center justify-center p-6">
          <Users className="text-muted-foreground mb-4 h-12 w-12 opacity-50" />
          <p className="text-center text-gray-500 dark:text-gray-400">
            No hay opciones de administración disponibles actualmente.
          </p>
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      {panelItems.map((item) => {
        const IconComponent = getIconComponent(item.icon) ?? ArrowRight;
        return (
          <Link href={route(item.route_name)} key={item.name} className="group block">
            <Card className="h-full overflow-hidden transition-all duration-200 ease-in-out hover:border-blue-500 hover:shadow-lg dark:hover:border-blue-400">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-xl font-semibold text-gray-800 group-hover:text-blue-600 dark:text-white dark:group-hover:text-blue-400">
                  {item.name}
                </CardTitle>
                <div className="rounded-full bg-gray-100 p-2.5 transition-colors group-hover:bg-blue-100 dark:bg-gray-800 dark:group-hover:bg-blue-950">
                  <IconComponent className="h-5 w-5 text-gray-600 group-hover:text-blue-600 dark:text-gray-400 dark:group-hover:text-blue-400" />
                </div>
              </CardHeader>
              <CardContent>
                <p className="text-sm text-gray-500 dark:text-gray-400">{item.description}</p>
                <div className="mt-4 flex items-center text-sm font-medium text-blue-600 group-hover:underline dark:text-blue-400">
                  Acceder
                  <ArrowRight className="ml-1.5 h-4 w-4 transition-transform group-hover:translate-x-1" />
                </div>
              </CardContent>
            </Card>
          </Link>
        );
      })}
    </div>
  );
};

export default AdminFeatureNavigationCards;
