import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AuthLayout from '@/layouts/auth-layout';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import React, { type FormEventHandler } from 'react';

interface ResetPasswordProps {
  token: string;
  email: string;
}

interface ResetPasswordForm {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export default function ResetPassword({ token, email }: Readonly<ResetPasswordProps>) {
  const form = useForm<Required<ResetPasswordForm>>({
    token: token,
    email: email,
    password: '',
    password_confirmation: '',
  });
  const { showSuccess, showError } = useToastNotifications();

  const mapError = React.useCallback((code?: string) => {
    if (!code) return '';
    if (code === 'validation.required') return 'Este campo es obligatorio.';
    if (code === 'validation.email') return 'Ingresa un correo electrónico válido.';
    if (code === 'validation.confirmed') return 'Las contraseñas no coinciden.';
    if (code === 'validation.min.string') return 'La contraseña es demasiado corta.';
    return code;
  }, []);

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    form.post(route('password.store'), {
      onFinish: () => {
        form.reset('password', 'password_confirmation');
      },
      onSuccess: () => {
        showSuccess('Tu contraseña ha sido restablecida correctamente.');
      },
      onError: (resp) => {
        const first = resp['email'] ?? resp['password'] ?? resp['password_confirmation'];
        const msg = mapError(first) || 'No se pudo restablecer la contraseña. Inténtalo de nuevo.';
        showError('Error al restablecer', { description: msg });
      },
    });
  };

  const emailError = React.useMemo(
    () => mapError(form.errors.email),
    [form.errors.email, mapError],
  );
  const passwordError = React.useMemo(
    () => mapError(form.errors.password),
    [form.errors.password, mapError],
  );
  const passwordConfError = React.useMemo(
    () => mapError(form.errors.password_confirmation),
    [form.errors.password_confirmation, mapError],
  );

  const anyError = emailError || passwordError || passwordConfError;

  return (
    <AuthLayout
      title="Restablecer contraseña"
      description="Por favor, ingresa tu nueva contraseña a continuación"
    >
      <Head title="Restablecer Contraseña" />

      {anyError && (
        <div
          className="mb-4 rounded-md border border-red-200 bg-red-50 px-3 py-2 text-center text-sm text-red-700"
          role="alert"
          aria-live="polite"
        >
          {emailError || passwordError || passwordConfError}
        </div>
      )}

      <form onSubmit={submit}>
        <div className="grid gap-6">
          <div className="grid gap-2">
            <Label htmlFor="email">Correo electrónico</Label>
            <Input
              id="email"
              type="email"
              name="email"
              autoComplete="email"
              value={form.data.email}
              className="mt-1 block w-full"
              readOnly
              onChange={(e) => {
                form.setData('email', e.target.value);
              }}
              aria-invalid={Boolean(emailError)}
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="password">Contraseña</Label>
            <Input
              id="password"
              type="password"
              name="password"
              autoComplete="new-password"
              value={form.data.password}
              className="mt-1 block w-full"
              onChange={(e) => {
                form.setData('password', e.target.value);
              }}
              placeholder="Contraseña"
              aria-invalid={Boolean(passwordError)}
            />
          </div>

          <div className="grid gap-2">
            <Label htmlFor="password_confirmation">Confirmar contraseña</Label>
            <Input
              id="password_confirmation"
              type="password"
              name="password_confirmation"
              autoComplete="new-password"
              value={form.data.password_confirmation}
              className="mt-1 block w-full"
              onChange={(e) => {
                form.setData('password_confirmation', e.target.value);
              }}
              placeholder="Confirmar contraseña"
              aria-invalid={Boolean(passwordConfError)}
            />
          </div>

          <Button
            type="submit"
            className="mt-4 w-full font-medium"
            disabled={form.processing}
            aria-busy={form.processing}
          >
            {form.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
            {form.processing ? 'Restableciendo…' : 'Restablecer contraseña'}
          </Button>
        </div>
      </form>
    </AuthLayout>
  );
}
