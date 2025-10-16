import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, UserRole } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData } from '@/utils/user-data';
import { Head, router, usePage } from '@inertiajs/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useEffect, useRef, useState } from 'react';
import { route } from 'ziggy-js';
import UserForm from '../components/user/user-form';
import CredentialShareDialog from '../dialogs/user/user-credential-share-dialog';
import { useUserForm } from '../hooks/use-user-form';
import type { UserCreatePageProps } from '../interfaces';

// Crear un cliente de React Query para usarlo en la aplicación
const queryClient = new QueryClient();

/**
 * Componente principal para la página de creación de usuario
 * @param props - Propiedades de la página
 * @returns El componente de la página
 */
export default function UserCreatePage({
  roles,
  auth,
  contextualNavItems,
  mainNavItems,
  moduleNavItems,
  globalNavItems,
  breadcrumbs,
  flash,
}: Readonly<UserCreatePageProps>) {
  useNavigationProgress({ delayMs: 150 });

  const userData = extractUserData(auth.user);

  useFlashToasts(
    flash
      ? {
          success: flash.success ?? '',
          error: flash.error ?? '',
          info: flash.info ?? '',
          warning: flash.warning ?? '',
        }
      : undefined,
  );

  // Fallback de breadcrumbs si no vienen desde el servidor
  const computedBreadcrumbs: BreadcrumbItem[] =
    breadcrumbs && breadcrumbs.length > 0
      ? breadcrumbs
      : createBreadcrumbs('internal.admin.users.create', 'Crear Nuevo Usuario');

  return (
    <AppLayout
      breadcrumbs={computedBreadcrumbs}
      user={userData}
      contextualNavItems={contextualNavItems ?? []}
      mainNavItems={mainNavItems ?? []}
      moduleNavItems={moduleNavItems ?? []}
      globalNavItems={globalNavItems ?? []}
    >
      <Head title="Crear Nuevo Usuario" />
      <div className="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div className="mb-8">
          <h1 className="text-foreground text-2xl font-semibold tracking-tight">
            Crear Nuevo Usuario
          </h1>
          <p className="text-muted-foreground mt-1 text-sm">
            Completa el formulario para crear un nuevo usuario con acceso al sistema
          </p>
        </div>

        <div className="mt-12">
          <QueryClientProvider client={queryClient}>
            <UserCreateManager roles={roles} authUserId={auth.user.id} />
          </QueryClientProvider>
        </div>
      </div>
    </AppLayout>
  );
}

interface UserCreateManagerProps {
  roles: UserRole[];
  authUserId: number;
}

/**
 * Componente gestor para la creación de usuarios
 * @param props - Propiedades del componente
 * @returns El componente gestor
 */
const UserCreateManager: React.FC<UserCreateManagerProps> = ({ roles, authUserId }) => {
  // Obtener errores de validación de la página
  const { errors } = usePage().props as { errors: Record<string, string> };
  const { showFieldError, showSuccess, showInfo } = useToastNotifications();

  // Estados para el diálogo de compartir credenciales
  const [isShareDialogOpen, setIsShareDialogOpen] = useState(false);
  const [createdUserData, setCreatedUserData] = useState<{
    name: string;
    email: string;
    password: string;
    auto_verify_email?: boolean;
  } | null>(null);
  const [isUserCreated, setIsUserCreated] = useState(false);

  // Referencia para controlar si ya se procesaron los errores
  const errorsProcessedRef = useRef(false);

  // Usar la lógica del formulario de usuario
  const { form, handleRoleChange, handleRoleKeyDown } = useUserForm(
    undefined, // No hay usuario inicial
    roles, // Pasar los roles disponibles
    authUserId,
  );

  // Función personalizada de envío para capturar las credenciales antes de enviar
  const handleSubmit = (data: typeof form.data) => {
    // Guardar los datos del usuario para mostrarlos en el diálogo
    setCreatedUserData({
      name: data.name,
      email: data.email,
      password: data.password,
      auto_verify_email: data.auto_verify_email,
    });

    // Convertir IDs de roles a nombres de roles
    const rolesAsNames = data.roles
      .map((roleId) => {
        const role = roles.find((r) => String(r.id) === roleId);
        return role?.name ?? '';
      })
      .filter(Boolean);

    // En lugar de actualizar el formulario, enviamos los datos modificados directamente
    const formData = {
      name: data.name,
      email: data.email,
      password: data.password,
      password_confirmation: data.password_confirmation,
      roles: rolesAsNames,
      // Enviar bandera para que el backend decida si verifica automáticamente
      auto_verify_email: data.auto_verify_email,
    } as const;

    // Enviar el formulario directamente usando router de Inertia
    const url = route('internal.admin.users.store');

    // Usar post directamente desde el router de Inertia, con opciones para evitar la redirección automática
    router.post(url, formData as unknown as FormData, {
      preserveScroll: true,
      preserveState: true, // Preservar el estado para evitar recargas
      replace: false, // No reemplazar la entrada en el historial
      onSuccess: (_page) => {
        // Prevenir cualquier redirección automática
        setIsUserCreated(true);
        setIsShareDialogOpen(true);
        showSuccess('Usuario creado con éxito');

        // Si NO se verificará automáticamente, avisar que se envió el correo de verificación
        if (!data.auto_verify_email) {
          showInfo(
            `Se envió un correo de verificación a ${data.email}. El usuario deberá verificar su correo para poder iniciar sesión.`,
          );
        }
      },
      onError: (_errors) => {
        // Manejar errores de validación
      },
      onFinish: () => {
        // Formulario procesado
      },
    });
  };

  // Mostrar el diálogo cuando el usuario ha sido creado
  useEffect(() => {
    if (isUserCreated && createdUserData && !isShareDialogOpen) {
      setIsShareDialogOpen(true);
    }
  }, [isUserCreated, createdUserData, isShareDialogOpen]);

  // Asignar errores de validación del backend al formulario
  useEffect(() => {
    // Usar la referencia para evitar múltiples actualizaciones
    if (Object.keys(errors).length > 0 && !errorsProcessedRef.current) {
      // Marcar como procesados para evitar múltiples actualizaciones
      errorsProcessedRef.current = true;

      // Asignar los errores del backend al formulario
      form.errors = errors;

      // Mostrar errores específicos para cada campo
      for (const [field, message] of Object.entries(errors)) {
        // Mostrar mensajes de error para campos específicos con un formato más descriptivo
        const fieldLabel = getFieldLabel(field);
        showFieldError(field, `${fieldLabel}: ${message}`);
      }
    } else if (Object.keys(errors).length === 0) {
      // Resetear la referencia cuando no hay errores
      errorsProcessedRef.current = false;
    }
  }, [errors, form, showFieldError]);

  // Función para obtener etiquetas legibles de los campos
  const getFieldLabel = (field: string): string => {
    const fieldLabels: Record<string, string> = {
      name: 'Nombre',
      email: 'Correo electrónico',
      roles: 'Roles',
    };

    // Manejar campos de contraseña dinámicamente
    if (field.includes('password')) {
      return 'Contraseña';
    }

    return fieldLabels[field] ?? field;
  };

  // Funciones para controlar el diálogo de compartir credenciales
  const handleCloseDialog = () => {
    setIsShareDialogOpen(false);
  };

  const handleFinishAndRedirect = () => {
    setIsShareDialogOpen(false);
    const url = route('internal.admin.users.index');
    globalThis.location.href = url;
  };

  return (
    <>
      <UserForm
        form={form}
        onSubmit={handleSubmit}
        submitButtonText="Crear Usuario"
        availableRoles={roles}
        isEditing={false}
        authUserId={authUserId}
        handleRoleChange={handleRoleChange}
        handleRoleKeyDown={handleRoleKeyDown}
      />
      {createdUserData && (
        <CredentialShareDialog
          isOpen={isShareDialogOpen}
          onClose={handleCloseDialog}
          onFinish={handleFinishAndRedirect}
          userName={createdUserData.name}
          userEmail={createdUserData.email}
          password={createdUserData.password}
        />
      )}
    </>
  );
};
