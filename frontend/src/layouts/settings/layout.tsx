import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { type NavItemDefinition } from '@/types';
import { cn } from '@/utils/cn';
import { Link } from '@inertiajs/react';
import { useEffect, useState, type PropsWithChildren } from 'react';

const sidebarNavItems: NavItemDefinition[] = [
  {
    title: 'Perfil',
    href: '/internal/settings/profile',
    icon: null,
  },
  {
    title: 'Contraseña',
    href: '/internal/settings/password',
    icon: null,
  },
  {
    title: 'Apariencia',
    href: '/internal/settings/appearance',
    icon: null,
  },
];

export default function SettingsLayout({ children }: Readonly<PropsWithChildren>) {
  const [currentPath, setCurrentPath] = useState('');

  useEffect(() => {
    setCurrentPath(globalThis.window.location.pathname);
  }, []);

  return (
    <div className="px-4 py-6">
      <Heading
        title="Configuración"
        description="Gestiona tu perfil y la configuración de tu cuenta"
      />

      <div className="flex flex-col space-y-8 lg:flex-row lg:space-y-0 lg:space-x-12">
        <aside className="w-full max-w-xl lg:w-48">
          <nav className="flex flex-col space-y-1 space-x-0">
            {sidebarNavItems.map((item) => (
              <Button
                key={item.href}
                size="default"
                variant="ghost"
                asChild
                className={cn('w-full justify-start', {
                  'bg-muted': currentPath === item.href,
                })}
              >
                <Link href={item.href}>{item.title}</Link>
              </Button>
            ))}
          </nav>
        </aside>

        <Separator className="my-6 md:hidden" />

        <div className="w-full flex-1 lg:max-w-none">
          <section className="w-full space-y-12">{children}</section>
        </div>
      </div>
    </div>
  );
}
