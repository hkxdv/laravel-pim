import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import { Link, useForm } from '@inertiajs/react';
import type { BasicInfoCardProps, BasicInfoForm } from './types';

export function BasicInfoCard({
  initialName,
  initialEmail,
  mustVerifyEmail,
  isStaffUser,
  emailVerifiedAt,
  status,
}: Readonly<BasicInfoCardProps>) {
  const { showSuccess, showError } = useToastNotifications();

  const form = useForm<Required<BasicInfoForm>>({
    name: initialName,
    email: initialEmail,
  });

  const formErrors = form.errors as Record<string, string | undefined>;

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    form.patch(route('internal.settings.profile.update'), {
      preserveScroll: true,
      onSuccess: () => {
        showSuccess('Perfil actualizado correctamente.');
        if (form.recentlySuccessful) {
          form.reset();
        }
      },
      onError: () => {
        showError('No se pudo actualizar el perfil.');
      },
    });
  };

  return (
    <Card className="max-w-[600px]">
      <CardHeader>
        <CardTitle>Información básica</CardTitle>
        <CardDescription>Actualiza tu nombre y dirección de correo electrónico</CardDescription>
      </CardHeader>
      <CardContent>
        <form onSubmit={submit} className="space-y-6">
          <div className="space-y-1">
            <Label htmlFor="name" className="text-muted-foreground text-sm font-normal">
              Nombre <span className="text-red-500">*</span>
            </Label>
            <Input
              id="name"
              value={form.data.name}
              onChange={(e) => {
                form.setData((prev) => ({ ...prev, name: e.target.value }));
              }}
              required
              autoComplete="name"
              placeholder="Nombre completo"
              aria-invalid={!!formErrors['name']}
              className={`bg-muted/40 h-11 px-4 focus-visible:ring-1 focus-visible:ring-offset-0 ${formErrors['name'] ? 'border-red-500 ring-1 ring-red-500' : ''}`}
            />
            {formErrors['name'] && (
              <div className="mt-1 flex items-center gap-1 text-sm text-red-500">
                <span>{formErrors['name']}</span>
              </div>
            )}
          </div>

          <div className="space-y-1">
            <Label htmlFor="email" className="text-muted-foreground text-sm font-normal">
              Correo electrónico <span className="text-red-500">*</span>
            </Label>
            <Input
              id="email"
              type="email"
              value={form.data.email}
              onChange={(e) => {
                form.setData((prev) => ({ ...prev, email: e.target.value }));
              }}
              required
              autoComplete="username"
              placeholder="Correo electrónico"
              aria-invalid={!!formErrors['email']}
              className={`bg-muted/40 h-11 px-4 focus-visible:ring-1 focus-visible:ring-offset-0 ${formErrors['email'] ? 'border-red-500 ring-1 ring-red-500' : ''}`}
            />
            {formErrors['email'] && (
              <div className="mt-1 flex items-center gap-1 text-sm text-red-500">
                <span>{formErrors['email']}</span>
              </div>
            )}
          </div>

          {mustVerifyEmail && isStaffUser && emailVerifiedAt === null && (
            <div className="mb-4 rounded-md border border-blue-300 bg-blue-50 p-3 text-sm text-blue-800 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400">
              <p>
                Tu correo electrónico no está verificado.{' '}
                <Link
                  href={route('verification.send')}
                  method="post"
                  as="button"
                  className="font-medium underline hover:text-blue-600"
                >
                  Haz clic aquí para reenviar el correo de verificación.
                </Link>
              </p>
              {status === 'verification-link-sent' && (
                <p className="mt-2 font-medium text-green-600">
                  Se ha enviado un nuevo enlace de verificación a tu correo electrónico.
                </p>
              )}
            </div>
          )}

          <div className="flex items-center gap-4 pt-4">
            <Button
              type="submit"
              disabled={form.processing || !form.isDirty}
              className="h-11 rounded-md bg-black font-medium text-white hover:bg-black/90 dark:bg-white dark:text-black dark:hover:bg-white/90"
            >
              {form.processing ? 'Guardando...' : 'Guardar perfil'}
            </Button>
          </div>
        </form>
      </CardContent>
    </Card>
  );
}
