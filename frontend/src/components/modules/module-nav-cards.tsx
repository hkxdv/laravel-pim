import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { ArrowRight } from 'lucide-react';
import { route } from 'ziggy-js';
import { type ModuleNavCardsProps } from './interfaces';

/**
 * Componente para mostrar tarjetas de navegación en paneles de módulos.
 * Cada tarjeta representa una sección del módulo y permite navegar a ella.
 */
export function ModuleNavCards({ items, getIconComponent }: Readonly<ModuleNavCardsProps>) {
  if (items.length === 0) {
    return null;
  }

  return (
    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
      {items.map((item) => {
        const IconComponent = getIconComponent(item.icon) ?? ArrowRight;

        // Determinar la URL correcta basada en el tipo de ítem
        let href: string;
        if ('href' in item && item.href) {
          href = item.href;
        } else if ('route' in item && item.route) {
          // Es un DirectRouteModuleNavItem
          href = item.route;
        } else if ('route_name' in item && item.route_name) {
          // Es un RouteNameModuleNavItem
          href = route(item.route_name);
        } else {
          // Fallback por si algo sale mal
          console.warn('ModuleNavItem sin ruta válida:', item);
          href = '#';
        }

        return (
          <Link href={href} key={item.name} className="group block">
            <Card className="hover:border-primary h-full overflow-hidden transition-all duration-200 ease-in-out hover:shadow-lg">
              <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle className="text-foreground group-hover:text-primary text-xl font-semibold">
                  {item.name}
                </CardTitle>
                <div className="bg-muted text-muted-foreground group-hover:bg-primary/10 rounded-full p-2.5 transition-colors">
                  <IconComponent className="h-5 w-5" />
                </div>
              </CardHeader>
              <CardContent>
                <p className="text-muted-foreground text-sm">{item.description}</p>
                <div className="text-primary mt-4 flex items-center text-sm font-medium group-hover:underline">
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
}
