import { Button } from '@/components/ui/button';
import { cn } from '@/utils/cn';
import { Link } from '@inertiajs/react';

export function WelcomeHero() {
  return (
    <section className="relative flex min-h-[calc(100vh-80px)] items-center justify-center border-b">
      <div
        aria-hidden="true"
        className="pointer-events-none absolute inset-0 opacity-[0.35] dark:opacity-[0.25]"
        style={{
          backgroundImage:
            'linear-gradient(to right, var(--tw-grid-color) 1px, transparent 1px), linear-gradient(to bottom, var(--tw-grid-color) 1px, transparent 1px)',
          backgroundSize: '32px 32px',
          // @ts-expect-error: CSS var para color de grid adaptable al tema
          ['--tw-grid-color']: 'var(--tw-border-color, rgba(0,0,0,0.06))',
        }}
      />

      <div className="relative z-10 container mx-auto px-4 py-16 sm:px-6 md:py-20 lg:px-8">
        <div className="mx-auto max-w-5xl text-center">
          {/* Badge */}
          <span className="bg-background text-foreground/80 ring-ring inline-flex items-center rounded-full border px-3 py-1 text-xs font-medium shadow-sm ring-1 ring-inset">
            Nuevo Lanzamiento
          </span>

          <h1
            className={cn(
              'mt-6 font-bold tracking-tight',
              'text-4xl sm:text-5xl md:text-6xl lg:text-7xl',
            )}
          >
            Este es un encabezado para tu nuevo
            <span className="block">proyecto</span>
          </h1>

          <p className="text-muted-foreground mx-auto mt-6 max-w-2xl text-base sm:text-lg">
            Lorem ipsum dolor sit amet consectetur adipisicing elit. Elig doloremque mollitia fugiat
            omnis! Porro facilis quo animi consequatur.
          </p>

          <div className="mt-10 flex items-center justify-center gap-3">
            <Link href="#comenzar">
              <Button size="lg" className="px-6">
                Comenzar
              </Button>
            </Link>
            <Link href="#mas-info">
              <Button variant="outline" size="lg" className="px-6">
                Más información
              </Button>
            </Link>
          </div>

          <div className="mt-16">
            <p className="text-muted-foreground text-sm">
              Impulsando la nueva generación de productos digitales
            </p>
            <div className="mt-4 flex flex-wrap items-center justify-center gap-x-8 gap-y-3">
              <BrandMark label="Shadcn/ui" />
              <BrandMark label="React" />
              <BrandMark label="Laravel" />
              <BrandMark label="Vite" />
            </div>
          </div>
        </div>
      </div>
    </section>
  );
}

function BrandMark({ label }: Readonly<{ label: string }>) {
  return (
    <div className="border-border bg-muted/40 text-muted-foreground inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-sm">
      <span className="bg-foreground/60 h-2 w-2 rounded-full" />
      <span className="font-medium">{label}</span>
    </div>
  );
}
