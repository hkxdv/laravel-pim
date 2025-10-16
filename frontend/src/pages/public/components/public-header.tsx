import { Button } from '@/components/ui/button';
import { cn } from '@/utils/cn';
import { Link } from '@inertiajs/react';
import { Menu, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { route } from 'ziggy-js';

// const navLinks = [{}];

/**
 * Encabezado público genérico de demostración.
 */
export function PublicHeader() {
  const [isScrolled, setIsScrolled] = useState(false);
  // const [hoveredIndex, setHoveredIndex] = useState<number | null>(null);
  // const { url } = usePage();

  useEffect(() => {
    const handleScroll = () => {
      if (window.scrollY > 10) {
        setIsScrolled(true);
      } else {
        setIsScrolled(false);
      }
    };

    window.addEventListener('scroll', handleScroll);

    return () => {
      window.removeEventListener('scroll', handleScroll);
    };
  }, []);

  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const toggleMenu = () => {
    setMobileMenuOpen(!mobileMenuOpen);
  };

  /*  // Filtrar dinámicamente los enlaces según la ruta actual
  const dynamicNavLinks = useMemo(() => {
    const currentPath = url.split('?')[0];
    return navLinks.filter((l) => l.href !== currentPath);
  }, [url]); */

  return (
    <header className="sticky top-0 z-50 w-full p-4">
      <div className="container mx-auto">
        <nav
          className={cn(
            'relative mx-auto flex items-center justify-between rounded-3xl px-3 py-3 transition-colors duration-500 sm:px-5',
            isScrolled
              ? 'border-border bg-background/40 supports-[backdrop-filter]:bg-background/60 border shadow-sm backdrop-blur'
              : 'border border-transparent',
          )}
        >
          <Link
            href={route('welcome', undefined, false)}
            className="flex items-center gap-2 pl-4"
            aria-label="Inicio"
          >
            <img src="/logo.svg" alt="Logo" className="h-5 w-auto" />
            <span className="text-sm font-medium">ModularStack</span>
          </Link>

          <ul
            className="relative mx-4 hidden items-center space-x-2 lg:flex"
            // onMouseLeave={() => setHoveredIndex(null)}
          >
            {/*   <AnimatePresence>
              {dynamicNavLinks.map((link, index) => (
                <li key={link.text}>
                  <HoverableNavLink
                    href={link.href}
                    Icon={link.Icon}
                    text={link.text}
                    isActive={hoveredIndex === index}
                    onMouseEnter={() => setHoveredIndex(index)}
                  />
                </li>
              ))}
            </AnimatePresence> */}
          </ul>

          <div className="flex items-center gap-3">
            {/* Botón de acceso (demo): navega a la página de login */}
            <Link href={route('login', undefined, false)}>
              <Button size="sm" className="px-4">
                Acceder
              </Button>
            </Link>

            {/* Mobile menu button */}
            <button
              type="button"
              className="bg-muted text-foreground hover:bg-muted/90 hover:text-foreground focus:ring-ring inline-flex items-center justify-center rounded-xl p-2.5 focus:ring-2 focus:outline-none focus:ring-inset md:hidden"
              onClick={toggleMenu}
              aria-expanded={mobileMenuOpen}
              aria-controls="mobile-menu"
              aria-label="Menu principal"
            >
              <span className="sr-only">Abrir menú principal</span>
              {mobileMenuOpen ? (
                <X className="h-5 w-5" aria-hidden="true" />
              ) : (
                <Menu className="h-5 w-5" aria-hidden="true" />
              )}
            </button>
          </div>
        </nav>
      </div>
    </header>
  );
}
