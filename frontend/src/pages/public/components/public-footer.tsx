import { Link } from '@inertiajs/react';

/**
 * Footer público genérico de demostración.
 * No está conectado a la lógica del proyecto; sirve como base visual.
 */
export function PublicFooter() {
  return (
    <footer className="border-border relative mt-24 border-t">
      <div
        aria-hidden="true"
        className="pointer-events-none absolute inset-x-0 bottom-0 overflow-hidden opacity-10 select-none"
      >
        <div className="flex w-full justify-center">
          <p className="text-foreground/30 text-center text-[8rem] leading-none font-extrabold tracking-tight whitespace-nowrap md:text-[10rem] lg:text-[14rem]">
            ModularStack
          </p>
        </div>
      </div>

      <div className="relative z-10 container mx-auto px-4 pt-24 pb-64 sm:px-6 lg:px-8">
        <nav className="flex flex-wrap items-center justify-center gap-x-6 gap-y-3 text-sm">
          <Link href="#product" className="text-foreground hover:underline">
            Producto
          </Link>
          <Link href="#about" className="text-foreground hover:underline">
            Sobre nosotros
          </Link>
          <Link href="#pricing" className="text-foreground hover:underline">
            Precios
          </Link>
          <Link href="#faq" className="text-foreground hover:underline">
            Preguntas frecuentes
          </Link>
          <Link href="#contact" className="text-foreground hover:underline">
            Contacto
          </Link>
          <Link href="#twitter" className="text-foreground hover:underline">
            Twitter ↗
          </Link>
          <Link href="#linkedin" className="text-foreground hover:underline">
            LinkedIn ↗
          </Link>
        </nav>

        <p className="text-muted-foreground mt-4 text-center text-xs">Política de privacidad</p>
      </div>
    </footer>
  );
}
