import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import React from 'react';

export default function VerifyEmail({ status }: Readonly<{ status?: string }>) {
  const { post, processing } = useForm({});
  const { showSuccess } = useToastNotifications();

  const submit = (e?: React.FormEvent) => {
    if (e) e.preventDefault();
    post(route('verification.send'));
  };

  // Notificar sólo cuando cambie el estado
  React.useEffect(() => {
    if (status === 'verification-link-sent') {
      showSuccess('Se ha enviado un nuevo enlace de verificación a tu correo.');
    }
  }, [showSuccess, status]);

  return (
    <AuthLayout
      title="Verifica tu Correo Electrónico"
      description="Antes de comenzar, verifica tu dirección de correo haciendo clic en el enlace que te enviamos. Si no lo recibiste, podemos reenviarlo."
    >
      <Head title="Verificación de Correo" />

      {status && (
        <div
          className={
            status === 'verification-link-sent'
              ? 'mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-center text-xs text-green-700'
              : 'border-muted-foreground/20 bg-background text-foreground mb-4 rounded-md border px-3 py-2 text-center text-xs'
          }
          role="status"
          aria-live="polite"
        >
          {status === 'verification-link-sent'
            ? 'Se ha enviado un nuevo enlace de verificación a tu correo.'
            : status}
        </div>
      )}

      <div className="space-y-6">
        <form onSubmit={submit} className="flex flex-col gap-3">
          <Button
            type="submit"
            disabled={processing}
            size="lg"
            className="w-full font-medium"
            aria-busy={processing}
          >
            {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
            {processing ? 'Reenviando…' : 'Reenviar correo de verificación'}
          </Button>
        </form>

        <p className="text-muted-foreground mt-1 text-center text-xs">
          Si no encuentras el correo, revisa tu carpeta de spam o correo no deseado.
        </p>

        <div className="text-muted-foreground text-center text-sm">
          <span>¿Ya verificaste tu cuenta?</span>
          <span className="mx-1">•</span>
          <TextLink href={route('login')}>Inicia sesión</TextLink>
        </div>
      </div>
    </AuthLayout>
  );
}
