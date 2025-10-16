import { Button } from '@/components/ui/button';
import { useTheme } from '@/providers/theme-provider';
import { MoonIcon, SunIcon } from '@radix-ui/react-icons';

export function ModeToggle() {
  const { theme, setTheme } = useTheme();

  const toggleTheme = () => {
    setTheme(theme === 'light' ? 'dark' : 'light');
  };

  return (
    <Button
      variant="outline"
      size="icon"
      onClick={toggleTheme}
      className="border-border bg-background fixed bottom-4 left-4 z-50 h-9 w-9 rounded-full backdrop-blur"
    >
      <SunIcon className="h-4 w-4 scale-100 rotate-0 transition-all dark:scale-0 dark:-rotate-90" />
      <MoonIcon className="absolute h-4 w-4 scale-0 rotate-90 transition-all dark:scale-100 dark:rotate-0" />
      <span className="sr-only">Cambiar tema</span>
    </Button>
  );
}
