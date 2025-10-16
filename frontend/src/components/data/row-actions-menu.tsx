import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuLabel,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { cn } from '@/utils/cn';
import { Link } from '@inertiajs/react';
import { Copy, MoreHorizontal } from 'lucide-react';
import { useState, type MouseEvent, type ReactNode } from 'react';

export interface RowActionItem {
  key: string;
  label: string;
  icon?: ReactNode;
  onClick?: (e: MouseEvent) => void; // compatibilidad hacia atrás
  disabled?: boolean;
  variant?: 'default' | 'destructive' | 'secondary';
  href?: string;
  target?: '_blank' | '_self' | '_parent' | '_top';
}

interface RowActionsMenuProps {
  items: RowActionItem[];
  align?: 'start' | 'end' | 'center';
  showCopyId?: boolean;
  idToCopy?: string | number;
  triggerAriaLabel?: string;
  buttonClassName?: string;
  tooltipLabel?: string;
}

export function RowActionsMenu({
  items,
  align = 'end',
  showCopyId = true,
  idToCopy,
  triggerAriaLabel = 'Abrir menú de acciones',
  buttonClassName,
  tooltipLabel = 'Acciones',
}: Readonly<RowActionsMenuProps>) {
  const [open, setOpen] = useState(false);

  const handleCopy = async () => {
    if (idToCopy === undefined) return;
    try {
      await navigator.clipboard.writeText(String(idToCopy));
    } catch (error) {
      console.warn('No se pudo copiar al portapapeles', error);
    }
  };

  return (
    <DropdownMenu open={open} onOpenChange={setOpen}>
      <TooltipProvider delayDuration={100}>
        <Tooltip>
          <TooltipTrigger asChild>
            <DropdownMenuTrigger asChild>
              <Button
                size="icon"
                variant="ghost"
                className={cn('h-8 w-8', buttonClassName)}
                aria-label={triggerAriaLabel}
              >
                <MoreHorizontal className="h-4 w-4" />
              </Button>
            </DropdownMenuTrigger>
          </TooltipTrigger>
          <TooltipContent side="left">{tooltipLabel}</TooltipContent>
        </Tooltip>
      </TooltipProvider>

      <DropdownMenuContent align={align}>
        <DropdownMenuLabel>Acciones</DropdownMenuLabel>
        {(showCopyId && idToCopy !== undefined) || items.length > 0 ? (
          <DropdownMenuSeparator />
        ) : null}

        {showCopyId && idToCopy !== undefined && (
          <DropdownMenuItem
            onSelect={() => {
              void handleCopy();
              setOpen(false);
            }}
          >
            <Copy className="mr-2 h-4 w-4" /> Copiar ID
          </DropdownMenuItem>
        )}

        {showCopyId && idToCopy !== undefined && items.length > 0 && <DropdownMenuSeparator />}

        {items.map((it) => {
          const content = (
            <>
              {it.icon ? (
                <span className="mr-2 inline-flex h-4 w-4 items-center justify-center">
                  {it.icon}
                </span>
              ) : null}
              {it.label}
            </>
          );

          if (it.href) {
            return (
              <DropdownMenuItem
                key={it.key}
                asChild
                disabled={it.disabled ?? false}
                className={cn(
                  it.variant === 'destructive' && 'text-destructive focus:text-destructive',
                )}
              >
                <Link
                  href={it.href}
                  target={it.target}
                  onClick={(e) => {
                    it.onClick?.(e as unknown as MouseEvent);
                    setOpen(false);
                  }}
                >
                  {content}
                </Link>
              </DropdownMenuItem>
            );
          }

          return (
            <DropdownMenuItem
              key={it.key}
              onSelect={(e) => {
                it.onClick?.(e as unknown as MouseEvent);
                setOpen(false);
              }}
              disabled={it.disabled ?? false}
              className={cn(
                it.variant === 'destructive' && 'text-destructive focus:text-destructive',
              )}
            >
              {content}
            </DropdownMenuItem>
          );
        })}
      </DropdownMenuContent>
    </DropdownMenu>
  );
}

export default RowActionsMenu;
