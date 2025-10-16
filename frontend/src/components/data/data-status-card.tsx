/* eslint-disable sonarjs/no-duplicate-string */
import { Card, CardContent } from '@/components/ui/card';
import { cn } from '@/utils/cn';
import { AlertTriangle, CheckCircle, Info } from 'lucide-react';
import { type ReactNode } from 'react';

/**
 * Tarjeta de estado/KPI (StatusCard).
 * Usa tokens Shadcn para coherencia en dark/light.
 */

type StatusCardVariant = 'default' | 'info' | 'success' | 'warning' | 'error';

interface StatusCardProps {
  title: string;
  message: string;
  icon?: ReactNode;
  variant?: StatusCardVariant;
  actions?: ReactNode;
  className?: string;
}

export function StatusCard({
  title,
  message,
  icon,
  variant = 'default',
  actions,
  className,
}: Readonly<StatusCardProps>) {
  // Estilos por variante usando tokens de diseño
  const variantStyles: Record<
    StatusCardVariant,
    { cardBg: string; border: string; title: string; message: string; iconColor: string }
  > = {
    default: {
      cardBg: 'bg-card',
      border: 'border-border',
      title: 'text-foreground',
      message: 'text-muted-foreground',
      iconColor: 'text-muted-foreground',
    },
    info: {
      cardBg: 'bg-card',
      border: 'border-border',
      title: 'text-foreground',
      message: 'text-muted-foreground',
      iconColor: 'text-primary',
    },
    success: {
      cardBg: 'bg-card',
      border: 'border-border',
      title: 'text-foreground',
      message: 'text-muted-foreground',
      iconColor: 'text-foreground',
    },
    warning: {
      cardBg: 'bg-card',
      border: 'border-border',
      title: 'text-foreground',
      message: 'text-muted-foreground',
      iconColor: 'text-foreground',
    },
    error: {
      cardBg: 'bg-card',
      border: 'border-border',
      title: 'text-foreground',
      message: 'text-muted-foreground',
      iconColor: 'text-foreground',
    },
  };

  const styles = variantStyles[variant];

  const defaultIcon = {
    default: <Info className={cn('h-5 w-5', styles.iconColor)} />,
    info: <Info className={cn('h-5 w-5', styles.iconColor)} />,
    success: <CheckCircle className={cn('h-5 w-5', styles.iconColor)} />,
    warning: <AlertTriangle className={cn('h-5 w-5', styles.iconColor)} />,
    error: <AlertTriangle className={cn('h-5 w-5', styles.iconColor)} />,
  }[variant];

  const iconToRender = icon ?? defaultIcon;

  return (
    <Card className={cn(styles.cardBg, styles.border, 'overflow-hidden', className)}>
      <CardContent className="flex items-center p-4">
        <div className="mr-3 flex-shrink-0">{iconToRender}</div>
        <div className="flex-grow">
          <h3 className={cn('font-semibold', styles.title)}>{title}</h3>
          <p className={cn('text-sm', styles.message)}>{message}</p>
        </div>
        {actions && <div className="ml-auto flex-shrink-0">{actions}</div>}
      </CardContent>
    </Card>
  );
}
