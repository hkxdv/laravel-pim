import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import PasswordField from '@/components/ui/password-field';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import type { BreadcrumbItem, NavItemDefinition } from '@/types';
import { extractUserData } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, useForm, usePage } from '@inertiajs/react';
import { type FormEventHandler, useEffect, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Configuración de contraseña',
    href: '/settings/password',
  },
];

interface PasswordPageProps extends PageProps {
  contextualNavItems?: NavItemDefinition[];
}

export default function PasswordPage() {
  // Obtener contextualNavItems y flash de las props de la página
  const { auth, contextualNavItems, flash } = usePage<PasswordPageProps>().props;
  const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
  });

  // Hook de notificaciones
  const { showSuccess, showError } = useToastNotifications();

  // Estado para mostrar detalles de fortaleza de contraseña
  const [showPasswordDetails, setShowPasswordDetails] = useState(false);

  // Estado para errores de validación del cliente
  const [clientErrors, setClientErrors] = useState({
    password_confirmation: '',
  });

  // Procesar mensajes flash del backend
  useEffect(() => {
    if (flash.success) {
      showSuccess(flash.success);
      if (form.recentlySuccessful) {
        form.reset();
      }
    }
    if (flash.error) {
      showError(flash.error);
    }
  }, [flash, form.recentlySuccessful, showSuccess, showError, form]);

  const validateClientSide = (): boolean => {
    let isValid = true;
    const newClientErrors = { password_confirmation: '' };

    // Comprobar si la confirmación de contraseña está vacía cuando hay contraseña
    if (form.data.password && !form.data.password_confirmation) {
      // eslint-disable-next-line sonarjs/no-hardcoded-passwords
      newClientErrors.password_confirmation = 'Debes confirmar la nueva contraseña';
      isValid = false;
    }

    // Comprobar si las contraseñas coinciden
    if (
      form.data.password &&
      form.data.password_confirmation &&
      form.data.password !== form.data.password_confirmation
    ) {
      // eslint-disable-next-line sonarjs/no-hardcoded-passwords
      newClientErrors.password_confirmation = 'La confirmación de contraseña no coincide';
      isValid = false;
    }

    setClientErrors(newClientErrors);

    if (!isValid && newClientErrors.password_confirmation) {
      showError(newClientErrors.password_confirmation);
    }
    return isValid;
  };

  const updatePassword: FormEventHandler = (e) => {
    e.preventDefault();

    if (!validateClientSide()) {
      return;
    }

    form.put(route('internal.settings.password.update'), {
      preserveScroll: true,
      onSuccess: () => {
        form.reset();
        showSuccess('Contraseña actualizada correctamente');
      },
      onError: (errs: Record<string, string>) => {
        if (Object.keys(errs).length > 0) {
          showError('No se pudo actualizar la contraseña. Revisa los errores.');
        }
      },
    });
  };

  // Función para alternar la visualización de detalles de contraseña
  const togglePasswordDetails = () => {
    setShowPasswordDetails(!showPasswordDetails);
  };

  return (
    <AppLayout
      user={extractUserData(auth.user)}
      breadcrumbs={breadcrumbs}
      contextualNavItems={contextualNavItems ?? []}
    >
      <Head title="Configuración de contraseña" />

      <SettingsLayout>
        <div className="space-y-8">
          <HeadingSmall
            title="Actualizar contraseña"
            description="Asegúrate de que tu cuenta utiliza una contraseña larga y aleatoria para mantener la seguridad"
          />

          <Card className="w-full max-w-xl">
            <CardHeader>
              <CardTitle>Seguridad y acceso</CardTitle>
              <CardDescription>Actualiza tu contraseña para proteger tu cuenta</CardDescription>
            </CardHeader>
            <CardContent>
              <form onSubmit={updatePassword} className="space-y-6">
                {/* Contraseña actual */}
                <div className="space-y-1">
                  <PasswordField
                    id="current_password"
                    label="Contraseña actual"
                    value={form.data.current_password}
                    onChange={(value) => {
                      form.setData('current_password', value);
                    }}
                    error={form.errors.current_password ?? ''}
                    required
                    showStrengthIndicator={false}
                    showGenerateButton={false}
                    placeholder="Ingresa tu contraseña actual"
                  />
                </div>

                {/* Nueva contraseña */}
                <div className="space-y-1">
                  <PasswordField
                    id="password"
                    label="Nueva contraseña"
                    value={form.data.password}
                    onChange={(value) => {
                      form.setData('password', value);
                    }}
                    error={form.errors.password ?? ''}
                    required
                    showStrengthIndicator={true}
                    showStrengthDetails={showPasswordDetails}
                    showGenerateButton={true}
                    placeholder="Ingresa una nueva contraseña fuerte"
                  />
                  <div className="text-right">
                    <button
                      type="button"
                      onClick={togglePasswordDetails}
                      className="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                    >
                      {showPasswordDetails ? 'Ocultar detalles' : 'Mostrar detalles'}
                    </button>
                  </div>
                </div>

                {/* Confirmar contraseña */}
                <div className="space-y-1">
                  <PasswordField
                    id="password_confirmation"
                    label="Confirmar contraseña"
                    value={form.data.password_confirmation}
                    onChange={(value) => {
                      form.setData('password_confirmation', value);
                    }}
                    error={form.errors.password_confirmation ?? clientErrors.password_confirmation}
                    required
                    showStrengthIndicator={false}
                    showGenerateButton={false}
                    placeholder="Confirma tu nueva contraseña"
                  />
                </div>

                <div className="flex items-center gap-4 pt-4">
                  <Button
                    type="submit"
                    disabled={form.processing || !form.isDirty}
                    className="h-11 rounded-md bg-black font-medium text-white hover:bg-black/90 dark:bg-white dark:text-black dark:hover:bg-white/90"
                  >
                    {form.processing ? 'Actualizando...' : 'Guardar contraseña'}
                  </Button>
                </div>
              </form>
            </CardContent>
          </Card>
        </div>
      </SettingsLayout>
    </AppLayout>
  );
}
