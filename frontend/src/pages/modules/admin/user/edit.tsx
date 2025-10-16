import { useFlashToasts } from '@/hooks/use-flash-toasts';
import { useNavigationProgress } from '@/hooks/use-navigation-progress';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem, StaffUser, UserRole } from '@/types';
import { createBreadcrumbs } from '@/utils/breadcrumbs';
import { extractUserData } from '@/utils/user-data';
import { Head, usePage } from '@inertiajs/react';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { useEffect } from 'react';
import UserForm from '../components/user/user-form';
import { useUserForm } from '../hooks/use-user-form';
import type { UserEditPageProps } from '../interfaces';

// Crear un cliente de React Query para usarlo en la aplicación
const queryClient = new QueryClient();

/**
 * Componente principal para la página de edición de usuario
 * @param props - Propiedades de la página
 * @returns El componente de la página
 */
export default function UserEditPage({
  user,
  roles,
  auth,
  contextualNavItems,
  mainNavItems,
  moduleNavItems,
  globalNavItems,
  breadcrumbs,
  flash,
}: Readonly<UserEditPageProps>) {
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
      : createBreadcrumbs('internal.admin.users.edit', `Editar Usuario: ${user.name}`);

  // Usamos los breadcrumbs tal como vienen en props
  return (
    <AppLayout
      breadcrumbs={computedBreadcrumbs}
      user={userData}
      contextualNavItems={contextualNavItems ?? []}
      mainNavItems={mainNavItems ?? []}
      moduleNavItems={moduleNavItems ?? []}
      globalNavItems={globalNavItems ?? []}
    >
      <Head title={`Editar Usuario: ${user.name}`} />
      <div className="container mx-auto px-4 py-8 sm:px-6 lg:px-8">
        <div className="mb-8">
          <h1 className="text-foreground text-2xl font-semibold tracking-tight">
            Editar Usuario: {user.name}
          </h1>
          <p className="text-muted-foreground mt-1 text-sm">
            Actualiza la información del usuario y sus permisos en el sistema
          </p>
        </div>

        <div className="mt-12">
          <QueryClientProvider client={queryClient}>
            <UserEditManager user={user} roles={roles} authUserId={auth.user.id} />
          </QueryClientProvider>
        </div>
      </div>
    </AppLayout>
  );
}

interface UserEditManagerProps {
  user: StaffUser;
  roles: UserRole[];
  authUserId: number;
}

/**
 * Componente gestor para la edición de usuarios
 * @param props - Propiedades del componente
 * @returns El componente gestor
 */
const UserEditManager: React.FC<UserEditManagerProps> = ({ user, roles, authUserId }) => {
  // Obtener errores de validación de la página
  const { errors } = usePage().props as { errors: Record<string, string> };
  const { showFieldError } = useToastNotifications();

  // Usar la lógica del formulario de usuario
  const { form, handleSubmit, handleRoleChange, handleRoleKeyDown } = useUserForm(
    user,
    roles,
    authUserId,
  );

  // Asignar errores de validación del backend al formulario
  useEffect(() => {
    if (Object.keys(errors).length > 0) {
      // Asignar los errores del backend al formulario
      form.errors = errors;

      // Mostrar errores específicos para cada campo
      for (const [field, message] of Object.entries(errors)) {
        // Mostrar mensajes de error para campos específicos con un formato más descriptivo
        const fieldLabel = getFieldLabel(field);
        showFieldError(field, `${fieldLabel}: ${message}`);
      }
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

  return (
    <UserForm
      form={form}
      onSubmit={handleSubmit}
      submitButtonText="Actualizar Usuario"
      availableRoles={roles}
      isEditing={true}
      authUserId={authUserId}
      userId={user.id}
      handleRoleChange={handleRoleChange}
      handleRoleKeyDown={handleRoleKeyDown}
    />
  );
};
