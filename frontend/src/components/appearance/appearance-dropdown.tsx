import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useTheme } from '@/providers/theme-provider';
import { Monitor, Moon, Sun } from 'lucide-react';
import type { HTMLAttributes } from 'react';

export default function AppearanceToggleDropdown({
  className = '',
  ...props
}: Readonly<HTMLAttributes<HTMLDivElement>>) {
  const { theme, setTheme } = useTheme();

  const getCurrentIcon = () => {
    switch (theme) {
      case 'dark': {
        return <Moon className="h-5 w-5" />;
      }
      case 'light': {
        return <Sun className="h-5 w-5" />;
      }
      default: {
        return <Monitor className="h-5 w-5" />;
      }
    }
  };

  return (
    <div className={className} {...props}>
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <Button variant="ghost" size="icon" className="h-9 w-9 rounded-md">
            {getCurrentIcon()}
            <span className="sr-only">Cambiar tema</span>
          </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem
            onClick={() => {
              setTheme('light');
            }}
          >
            <span className="flex items-center gap-2">
              <Sun className="h-5 w-5" />
              Claro
            </span>
          </DropdownMenuItem>
          <DropdownMenuItem
            onClick={() => {
              setTheme('dark');
            }}
          >
            <span className="flex items-center gap-2">
              <Moon className="h-5 w-5" />
              Oscuro
            </span>
          </DropdownMenuItem>
          <DropdownMenuItem
            onClick={() => {
              setTheme('system');
            }}
          >
            <span className="flex items-center gap-2">
              <Monitor className="h-5 w-5" />
              Sistema
            </span>
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    </div>
  );
}
