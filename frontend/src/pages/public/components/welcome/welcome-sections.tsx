import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/utils/cn';
import { Link } from '@inertiajs/react';

/**
 * Componente genérico de demostración.
 */
export function WelcomeSections() {
  return (
    <div className="border-border/50 container mx-auto space-y-24 px-4 py-16 sm:px-6 lg:px-8">
      {/* Características */}
      <section id="caracteristicas" className="mx-auto max-w-6xl">
        <div className="text-center">
          <h2 className={cn('text-2xl font-semibold sm:text-3xl')}>Características principales</h2>
          <p className="text-muted-foreground mx-auto mt-2 max-w-2xl">
            Un conjunto de componentes y utilidades pensadas para acelerar el desarrollo.
          </p>
        </div>

        <div className="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <FeatureCard
            title="Componentes reutilizables"
            description="UI consistente con estilos listos para producción."
          />
          <FeatureCard
            title="Arquitectura modular"
            description="Organiza tu código por módulos y dominios."
          />
          <FeatureCard
            title="Tipado estricto"
            description="Interfaces claras para minimizar errores en tiempo de desarrollo."
          />
        </div>
      </section>

      {/* CTA amplia */}
      <section id="comenzar" className="mx-auto max-w-5xl">
        <div className="bg-muted/50 border-border relative overflow-hidden rounded-3xl border p-8 sm:p-12">
          <div
            className="pointer-events-none absolute inset-0 opacity-20"
            style={{
              backgroundImage:
                'radial-gradient(36rem 36rem at 10% 10%, hsl(var(--primary)/.25), transparent 60%), radial-gradient(36rem 36rem at 90% 90%, hsl(var(--primary)/.25), transparent 60%)',
            }}
          />
          <div className="relative z-10 text-center">
            <h3 className="text-xl font-semibold sm:text-2xl">¿Listo para empezar?</h3>
            <p className="text-muted-foreground mx-auto mt-2 max-w-2xl">
              Explora la documentación y crea tu primera página en minutos.
            </p>
            <div className="mt-6 flex items-center justify-center gap-3">
              <Link href="#mas-info">
                <Button size="lg" className="px-6">
                  Ver guía rápida
                </Button>
              </Link>
              <Link href="#contacto">
                <Button variant="outline" size="lg" className="px-6">
                  Contactar
                </Button>
              </Link>
            </div>
          </div>
        </div>
      </section>

      {/* FAQ breve */}
      <section id="faq" className="mx-auto max-w-5xl">
        <div className="text-center">
          <h3 className="text-xl font-semibold sm:text-2xl">Preguntas frecuentes</h3>
          <p className="text-muted-foreground mx-auto mt-2 max-w-2xl">
            Respuestas rápidas para dudas comunes durante la fase de demo.
          </p>
        </div>
        <div className="mt-8 grid gap-6 sm:grid-cols-2">
          <FaqItem
            q="¿Esta página es funcional?"
            a="Es una demo visual. El contenido es genérico y no representa funcionalidades finales."
          />
          <FaqItem
            q="¿Se puede personalizar?"
            a="Sí. Los estilos usan Tailwind y componentes reutilizables."
          />
          <FaqItem
            q="¿Dónde encuentro más ejemplos?"
            a="Puedes agregar secciones adicionales en este mismo componente o crear nuevos."
          />
          <FaqItem
            q="¿Cómo reporto un error?"
            a="Abre un issue en el repositorio o comunica por el canal interno."
          />
        </div>
      </section>
    </div>
  );
}

function FeatureCard({ title, description }: Readonly<{ title: string; description: string }>) {
  return (
    <Card className="h-full">
      <CardHeader>
        <CardTitle className="text-lg">{title}</CardTitle>
      </CardHeader>
      <CardContent>
        <p className="text-muted-foreground text-sm">{description}</p>
      </CardContent>
    </Card>
  );
}

function FaqItem({ q, a }: Readonly<{ q: string; a: string }>) {
  return (
    <div className="rounded-2xl border p-5">
      <p className="font-medium">{q}</p>
      <p className="text-muted-foreground mt-1 text-sm">{a}</p>
    </div>
  );
}
