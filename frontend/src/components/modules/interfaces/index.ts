/**
 * Tipos compartidos para los componentes de módulos reutilizables.
 */
import type { ModuleNavItem } from '@/types';
import type { IconName } from '@/utils/lucide-icons';
import { type LucideIcon } from 'lucide-react';

/**
 * Props para el encabezado de módulo.
 */
export interface ModuleHeaderProps {
  /** Título principal del módulo */
  title: string;
  /** Subtítulo o descripción del módulo (opcional) */
  description?: string;
  /** Nombre del usuario a mostrar en el saludo */
  userName: string;
  /** Mostrar saludo personalizado (opcional, por defecto: true) */
  showGreeting?: boolean;
}

/**
 * Props para las tarjetas de navegación del módulo.
 */
export interface ModuleNavCardsProps {
  /** Elementos de navegación a mostrar como tarjetas */
  items: ModuleNavItem[];
  /** Función para obtener el componente de icono a partir de un nombre */
  getIconComponent: (icon?: string | LucideIcon | null) => LucideIcon | null;
}

/**
 * Props para el componente de estado vacío.
 */
export interface ModuleEmptyStateProps {
  /** Mensaje a mostrar cuando no hay elementos */
  message?: string;
  /** Nombre del icono a mostrar */
  icon?: IconName;
  /** Componente de icono personalizado */
  IconComponent?: LucideIcon;
}
