import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import React, { type FormEventHandler } from 'react';

import { useToastNotifications } from '@/hooks/use-toast-notifications';

export default function ConfirmPassword() {
  const form = useForm<Required<{ password: string }>>({
    password: '',
  });
  const { showError, showSuccess } = useToastNotifications();

  const submit: FormEventHandler = (e) => {
    e.preventDefault();

    form.post(route('password.confirm'), {
      onFinish: () => {
        form.reset();
      },
      onError: () => {
        const message =
          form.errors.password === 'validation.required'
            ? 'La contraseña es obligatoria.'
            : (form.errors.password ?? 'Error al confirmar contraseña.');
        showError('No se pudo confirmar', { description: message });
      },
      onSuccess: () => {
        showSuccess('Contraseña confirmada correctamente.');
      },
    });
  };

  const errorMessage = React.useMemo(() => {
    const code = form.errors.password;
    if (!code) return '';
    if (code === 'validation.required') return 'La contraseña es obligatoria.';
    return code;
  }, [form.errors.password]);

  return (
    <AuthLayout
      title="Confirma tu contraseña"
      description="Esta es un área segura de la aplicación. Por favor, confirma tu contraseña antes de continuar."
    >
      <Head title="Confirmar Contraseña" />

      {errorMessage && (
        <div
          className="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-center text-sm text-red-700"
          role="alert"
          aria-live="polite"
        >
          {errorMessage}
        </div>
      )}

      <form onSubmit={submit}>
        <div className="space-y-6">
          <div className="grid gap-2">
            <Label htmlFor="password">Contraseña</Label>
            <Input
              id="password"
              type="password"
              name="password"
              placeholder="Contraseña"
              autoComplete="current-password"
              value={form.data.password}
              onChange={(e) => {
                form.setData('password', e.target.value);
              }}
              aria-invalid={Boolean(errorMessage)}
            />
          </div>

          <div className="flex items-center">
            <Button
              className="w-full font-medium"
              disabled={form.processing}
              aria-busy={form.processing}
            >
              {form.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
              {form.processing ? 'Confirmando…' : 'Confirmar Contraseña'}
            </Button>
          </div>
        </div>
      </form>
    </AuthLayout>
  );
}
