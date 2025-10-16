import {
  Activity,
  AlertTriangle,
  ArrowLeft,
  ArrowRight,
  BarChart,
  BarChart2,
  BarChart3,
  BookOpen,
  BookOpenCheck,
  Calendar,
  CalendarCheck,
  CalendarPlus,
  CheckCircle,
  ClipboardList,
  Clock,
  FilePlus2,
  FileText,
  Home,
  Hourglass,
  KeyRound,
  LayoutDashboard,
  LayoutGrid,
  List,
  Lock,
  Package,
  Palette,
  Pencil,
  ScrollText,
  ServerCog,
  Settings,
  Shield,
  ShieldAlert,
  ShieldCheck,
  SquareChevronLeft,
  TrendingUp,
  UserCog,
  UserPlus,
  Users,
  XCircle,
  type LucideIcon,
} from 'lucide-react';

// Lista de nombres de íconos soportados por la app (PascalCase)
export const LUCIDE_ICON_NAMES = [
  'Activity',
  'AlertTriangle',
  'ArrowLeft',
  'ArrowRight',
  'BarChart',
  'BarChart2',
  'BarChart3',
  'BookOpen',
  'BookOpenCheck',
  'Calendar',
  'CalendarCheck',
  'CalendarPlus',
  'CheckCircle',
  'ClipboardList',
  'Clock',
  'FilePlus2',
  'FileText',
  'Home',
  'Hourglass',
  'KeyRound',
  'LayoutDashboard',
  'LayoutGrid',
  'List',
  'Lock',
  'Package',
  'Palette',
  'Pencil',
  'ScrollText',
  'ServerCog',
  'Settings',
  'Shield',
  'ShieldAlert',
  'ShieldCheck',
  'SquareChevronLeft',
  'TrendingUp',
  'UserCog',
  'UserPlus',
  'Users',
  'XCircle',
] as const;

export type IconName = (typeof LUCIDE_ICON_NAMES)[number];

/**
 * Mapea un nombre de ícono (string) al componente de Lucide React correspondiente.
 * Utilidad global para toda la aplicación.
 *
 * @param {string | LucideIcon | undefined | null} iconName - El nombre del ícono o un componente LucideIcon.
 * @returns {LucideIcon | null} - Devuelve el componente LucideIcon o null.
 */
export const getLucideIcon = (iconName?: string | LucideIcon | null): LucideIcon | null => {
  if (!iconName) return null;
  if (typeof iconName !== 'string') return iconName;

  // Normalizar el nombre del ícono (eliminar espacios, convertir a minúsculas)
  const normalizedName = iconName.toLowerCase().trim();

  // Mapeo de nombres normalizados a componentes de LucideIcon
  // Esto reemplaza la declaración switch para reducir el número de casos y mejorar la escalabilidad.
  const lucideIconMap: Record<string, LucideIcon> = {
    activity: Activity,
    alerttriangle: AlertTriangle,
    arrowleft: ArrowLeft,
    arrowright: ArrowRight,
    barchart: BarChart,
    barchart2: BarChart2,
    barchart3: BarChart3,
    bookopen: BookOpen,
    bookopencheck: BookOpenCheck,
    calendar: Calendar,
    calendarcheck: CalendarCheck,
    calendarplus: CalendarPlus,
    checkcircle: CheckCircle,
    clipboardlist: ClipboardList,
    clock: Clock,
    fileplus2: FilePlus2,
    filetext: FileText,
    home: Home,
    hourglass: Hourglass,
    keyround: KeyRound,
    layoutdashboard: LayoutDashboard,
    layoutgrid: LayoutGrid,
    list: List,
    lock: Lock,
    package: Package,
    palette: Palette,
    pencil: Pencil,
    servercog: ServerCog,
    settings: Settings,
    shield: Shield,
    shieldalert: ShieldAlert,
    shieldcheck: ShieldCheck,
    scrolltext: ScrollText,
    squarechevronleft: SquareChevronLeft,
    trendingup: TrendingUp,
    usercog: UserCog,
    userplus: UserPlus,
    users: Users,
    xcircle: XCircle,
  };

  return lucideIconMap[normalizedName] ?? null;
};
