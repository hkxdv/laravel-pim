import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { cn } from '@/utils/cn';
import { AlertTriangle, CheckCircle, Info, Loader2 } from 'lucide-react';
import * as React from 'react';

type Variant = 'default' | 'destructive' | 'warning' | 'success' | 'info';

interface ConfirmationDialogProps {
  isOpen: boolean;
  onClose: () => void;
  onConfirm: () => void;
  title: string;
  description: React.ReactNode;
  isConfirming?: boolean;
  variant?: Variant;
  confirmText?: string;
  cancelText?: string;
  contentClassName?: string;
  icon?: React.ReactNode;
  buttonVariant?: Variant;
}

export const ConfirmationDialog: React.FC<ConfirmationDialogProps> = ({
  isOpen,
  onClose,
  onConfirm,
  title,
  description,
  isConfirming = false,
  variant = 'destructive',
  confirmText = 'Confirmar',
  cancelText = 'Cancelar',
  contentClassName,
  icon,
  buttonVariant,
}) => {
  const variantConfig: Record<
    Variant,
    {
      icon: React.ReactNode;
      iconContainerClassName: string;
      buttonVariant: Variant;
    }
  > = {
    default: {
      icon: <Info className="h-6 w-6" />,
      iconContainerClassName: 'border-border bg-muted text-muted-foreground',
      buttonVariant: 'default',
    },
    destructive: {
      icon: <AlertTriangle className="h-6 w-6" />,
      iconContainerClassName: 'border-destructive/20 bg-destructive/10 text-destructive',
      buttonVariant: 'destructive',
    },
    warning: {
      icon: <AlertTriangle className="h-6 w-6" />,
      iconContainerClassName: 'border-yellow-500/20 bg-yellow-500/10 text-yellow-500',
      buttonVariant: 'warning',
    },
    success: {
      icon: <CheckCircle className="h-6 w-6" />,
      iconContainerClassName: 'border-green-500/20 bg-green-500/10 text-green-500',
      buttonVariant: 'success',
    },
    info: {
      icon: <Info className="h-6 w-6" />,
      iconContainerClassName: 'border-primary/20 bg-primary/10 text-primary',
      buttonVariant: 'info',
    },
  };

  const config = variantConfig[variant];

  return (
    <Dialog open={isOpen} onOpenChange={onClose}>
      <DialogContent className={cn('sm:max-w-md', contentClassName)}>
        <DialogHeader className="flex flex-col items-center text-center">
          <div className={cn('rounded-full border p-2', config.iconContainerClassName)}>
            {icon ?? config.icon}
          </div>
          <DialogTitle className="mt-2 text-[16px] font-semibold">{title}</DialogTitle>
          <DialogDescription className="mt-1 text-center">{description}</DialogDescription>
        </DialogHeader>
        <DialogFooter className="mt-4 flex-col-reverse gap-2 sm:flex-row sm:justify-center">
          <Button variant="outline" onClick={onClose} disabled={isConfirming}>
            {cancelText}
          </Button>
          <Button
            variant={buttonVariant ?? config.buttonVariant}
            onClick={onConfirm}
            disabled={isConfirming}
          >
            {isConfirming && <Loader2 className="mr-2 h-4 w-4 animate-spin" />}
            {confirmText}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
};
