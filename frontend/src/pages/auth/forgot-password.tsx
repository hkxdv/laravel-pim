import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import React, { type FormEventHandler } from 'react';

export default function ForgotPassword({ status }: Readonly<{ status?: string }>) {
  const { data, setData, post, processing, errors } = useForm<Required<{ email: string }>>({
    email: '',
  });
  const { showSuccess } = useToastNotifications();

  // Mostrar notificación cuando exista un estado de éxito desde el servidor
  React.useEffect(() => {
    if (status) {
      showSuccess(status);
    }
  }, [showSuccess, status]);

  // Mapeo de mensajes de validación a textos legibles (evitar claves crudas como "validation.required")
  const errorMessage = React.useMemo(() => {
    const code = errors.email;
    if (!code) return '';
    if (code === 'validation.required') return 'El correo electrónico es obligatorio.';
    if (code === 'validation.email') return 'Ingresa un correo electrónico válido.';
    return code;
  }, [errors.email]);

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    post(route('password.email'));
  };

  return (
    <AuthLayout
      title="¿Olvidaste tu contraseña?"
      description="Ingresa tu correo electrónico para recibir un enlace de restablecimiento de contraseña"
    >
      <Head title="Restablecer Contraseña" />

      {status && (
        <div
          className="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-center text-xs text-green-700"
          role="status"
          aria-live="polite"
        >
          {status}
        </div>
      )}

      {errorMessage && (
        <div
          className="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-center text-xs text-red-700"
          role="alert"
          aria-live="polite"
        >
          {errorMessage}
        </div>
      )}

      <div className="space-y-6">
        <form onSubmit={submit}>
          <div className="grid gap-2">
            <Label htmlFor="email">Correo electrónico</Label>
            <Input
              id="email"
              type="email"
              name="email"
              autoComplete="off"
              value={data.email}
              onChange={(e) => {
                setData('email', e.target.value);
              }}
              placeholder="correo@ejemplo.com"
              aria-invalid={Boolean(errorMessage)}
            />

            {/* Se elimina el error en línea para evitar mostrar claves crudas como "validation.required" */}
          </div>

          <div className="my-6 flex items-center justify-start">
            <Button className="w-full font-medium" disabled={processing} aria-busy={processing}>
              {processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
              {processing ? 'Enviando…' : 'Enviar enlace de restablecimiento'}
            </Button>
          </div>
        </form>

        <div className="text-muted-foreground space-x-1 text-center text-sm">
          <span>O, vuelve a</span>
          <TextLink href={route('login')}>Iniciar sesión</TextLink>
        </div>
      </div>
    </AuthLayout>
  );
}
