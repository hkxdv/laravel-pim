import { cn } from '@/utils/cn';
import type { HTMLAttributes } from 'react';
import { Separator } from './separator';

interface FormSectionProps extends HTMLAttributes<HTMLDivElement> {
  title: string;
  description: string;
  /**
   * Controla si se muestra el separador superior
   * @default true
   */
  showTopSeparator?: boolean;
  /**
   * Controla si se muestra el separador inferior
   * @default true
   */
  showBottomSeparator?: boolean;
  contentMaxWidthClass?: string;
}

export function FormSection({
  title,
  description,
  className,
  children,
  showTopSeparator = true,
  showBottomSeparator = true,
  contentMaxWidthClass = 'max-w-2xl',
}: Readonly<FormSectionProps>) {
  return (
    <div className={cn('space-y-12', className)}>
      {showTopSeparator && <Separator />}
      <div className="grid grid-cols-1 gap-x-8 gap-y-10 py-4 md:grid-cols-3">
        <div className="md:col-span-1">
          <h2 className="text-foreground text-base leading-7 font-semibold">{title}</h2>
          <p className="text-muted-foreground mt-1 text-sm leading-6">{description}</p>
        </div>

        <div
          className={cn(
            'grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6 md:col-span-2',
            contentMaxWidthClass,
          )}
        >
          {children}
        </div>
      </div>
      {showBottomSeparator && <Separator />}
    </div>
  );
}
