import { useTheme } from '@/providers/theme-provider';
import { Toaster as SonnerPrimitive, type ToasterProps } from 'sonner';

const Toaster = ({ ...props }: ToasterProps) => {
  const { theme } = useTheme();
  const safeTheme = theme as NonNullable<ToasterProps['theme']>;

  return (
    <SonnerPrimitive
      theme={safeTheme}
      className="toaster group"
      toastOptions={{
        classNames: {
          toast:
            'group toast group-[.toaster]:bg-card group-[.toaster]:text-foreground group-[.toaster]:border group-[.toaster]:border-border group-[.toaster]:shadow-lg group-[.toaster]:rounded-lg',
          description: 'group-[.toast]:text-muted-foreground',
          actionButton: 'group-[.toast]:bg-primary group-[.toast]:text-primary-foreground',
          cancelButton: 'group-[.toast]:bg-muted group-[.toast]:text-muted-foreground',
          success:
            'group-[.toaster]:text-foreground group-[.toaster]:border-success group-[.toaster]:border-l-4',
          error:
            'group-[.toaster]:text-foreground group-[.toaster]:border-destructive group-[.toaster]:border-l-4',
          warning:
            'group-[.toaster]:text-foreground group-[.toaster]:border-warning group-[.toaster]:border-l-4',
          info: 'group-[.toaster]:text-foreground group-[.toaster]:border-primary group-[.toaster]:border-l-4',
          icon: 'text-current',
        },
        duration: 5000,
      }}
      {...props}
    />
  );
};

export { Toaster };
