import { useToastNotifications } from '@/hooks/use-toast-notifications';
import type { StaffUser, UserRole } from '@/types';
import { router, useForm } from '@inertiajs/react';

/**
 * Datos del formulario de usuario
 */
export interface UserFormData {
  name: string;
  email: string;
  password: string;
  password_confirmation: string;
  roles: string[]; // Array de nombres de roles
  auto_verify_email: boolean; // Opción para verificar automáticamente el email
  [key: string]: string | string[] | boolean;
}

/**
 * Errores del formulario de usuario
 */
export interface UserFormErrors {
  name?: string;
  email?: string;
  password?: string;
  password_confirmation?: string;
  roles?: string;
}

// Tipo para el valor que puede recibir setData
type FormDataValue = string | string[];

/**
 * Interfaz para el objeto de formulario retornado por useForm
 */
export interface UserForm {
  data: UserFormData;
  setData: (key: keyof UserFormData | string, value: FormDataValue) => void;
  errors: UserFormErrors;
  processing: boolean;
  post: (url: string, options?: { preserveScroll?: boolean; onSuccess?: () => void }) => void;
  put: (url: string, options?: { preserveScroll?: boolean }) => void;
  reset: () => void;
}

/**
 * Extrae los IDs de roles de un usuario
 * @param user Usuario del que extraer los roles
 * @returns Array de IDs de roles como strings
 */
export const extractRoleIds = (user?: StaffUser): string[] => {
  if (!user?.roles) return [];

  if (Array.isArray(user.roles)) {
    return user.roles.map((role) => String(role.id));
  } else if (typeof user.roles === 'object' && 'id' in user.roles) {
    return [String((user.roles as { id: string | number }).id)];
  }

  return [];
};

/**
 * Inicializa los datos del formulario con los valores proporcionados
 * @param initialUser Usuario inicial (para edición)
 * @returns Datos iniciales del formulario
 */
const initializeFormData = (initialUser?: StaffUser): UserFormData => {
  return {
    name: initialUser?.name ?? '',
    email: initialUser?.email ?? '',
    password: '',
    password_confirmation: '',
    roles: extractRoleIds(initialUser),
    auto_verify_email: true, // Por defecto, verificar automáticamente el email
  };
};

/**
 * Función para iniciar la eliminación de un usuario.
 * @param user - El objeto de usuario a eliminar.
 */
const deleteUser = (user: StaffUser) => {
  // Usamos confirm() para una verificación de seguridad simple del lado del cliente.
  const userName = typeof user.name === 'string' ? user.name : 'seleccionado';
  if (confirm(`¿Estás seguro de que deseas eliminar al usuario "${userName}"?`)) {
    router.delete(route('internal.admin.users.destroy', { id: user.id }), {
      preserveScroll: true,
      // El backend debe responder con un mensaje flash para notificar el resultado.
    });
  }
};

/**
 * Hook personalizado para manejar la lógica del formulario de usuario
 * @param initialUser Usuario inicial (para edición)
 * @param availableRoles Roles disponibles para asignar
 * @param authUserId ID del usuario autenticado
 * @returns Objeto con el formulario y funciones de manejo
 */
export const useUserForm = (
  initialUser?: StaffUser,
  availableRoles?: UserRole[],
  authUserId?: number,
) => {
  // Inicializar el formulario con useForm de Inertia.js
  const form = useForm<UserFormData>(initializeFormData(initialUser));
  const { showError } = useToastNotifications();

  /**
   * Convierte IDs de roles a nombres de roles para enviar al backend
   * @param roleIds Array de IDs de roles
   * @returns Array de nombres de roles
   */
  const convertRoleIdsToNames = (roleIds: string[]): string[] => {
    if (!availableRoles) return [];

    return roleIds
      .map((roleId) => {
        const role = availableRoles.find((r) => String(r.id) === roleId);
        return role?.name ?? '';
      })
      .filter((name) => name !== '');
  };

  /**
   * Maneja el envío del formulario para crear o editar un usuario.
   * @param data Datos del formulario.
   */
  const handleSubmit = (data: UserFormData) => {
    const isEditing = !!initialUser;
    const method = isEditing ? 'put' : 'post';
    const url = isEditing
      ? route('internal.admin.users.update', { id: initialUser.id })
      : route('internal.admin.users.store');

    // Convertir IDs de roles a nombres de roles antes de enviar
    const formData = {
      ...data,
      roles: convertRoleIdsToNames(data.roles),
    };

    // Enviar el formulario y dejar que el backend maneje la validación
    router[method](url, formData, {
      onSuccess: () => {
        if (method === 'post') {
          form.reset();
        }
      },
    });
  };

  /**
   * Maneja la eliminación de un usuario.
   */
  const handleDelete = () => {
    if (authUserId === initialUser?.id) {
      showError('No puedes eliminar tu propia cuenta mientras estás autenticado.');
      return;
    }
    if (confirm('¿Estás seguro de que deseas eliminar este usuario?')) {
      router.delete(route('internal.admin.users.destroy', { id: initialUser?.id }));
    }
  };

  /**
   * Maneja el cambio de un rol en el formulario
   * @param roleId ID del rol a cambiar
   * @param checked Estado de selección
   * @param isRoleDisabled Función que determina si un rol está deshabilitado
   */
  const handleRoleChange = (
    roleId: string,
    checked: boolean,
    isRoleDisabled: (roleId: string) => boolean,
  ) => {
    // Si el rol está deshabilitado, no permitir cambios
    if (isRoleDisabled(roleId)) {
      return;
    }

    const updatedRoles: string[] = checked
      ? [...form.data.roles, roleId]
      : form.data.roles.filter((id) => id !== roleId);

    form.setData({
      ...form.data,
      roles: updatedRoles,
    });
  };

  /**
   * Maneja eventos de teclado para roles
   */
  const handleRoleKeyDown = (
    event: React.KeyboardEvent<HTMLDivElement>,
    roleId: string,
    checked: boolean,
    isRoleDisabled: (roleId: string) => boolean,
  ) => {
    if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      handleRoleChange(roleId, !checked, isRoleDisabled);
    }
  };

  return {
    form,
    handleSubmit,
    handleDelete,
    handleRoleChange,
    handleRoleKeyDown,
  };
};

/**
 * Hook para gestionar la eliminación de un usuario.
 * @returns Una función `deleteUser` para iniciar el proceso de eliminación.
 */
export function useDeleteUser() {
  return { deleteUser };
}
