import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import PasswordField from '@/components/ui/password-field';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import type { UserRole } from '@/types';
import { AlertCircle } from 'lucide-react';
import React, { useState } from 'react';
import type { UserFormData } from '../../hooks/use-user-form';

interface UserFormProps {
  form: {
    data: UserFormData;
    setData: (key: keyof UserFormData, value: string | string[] | boolean) => void;
    errors: Partial<Record<keyof UserFormData, string>>;
    processing: boolean;
  };
  onSubmit: (data: UserFormData) => void;
  submitButtonText: string;
  availableRoles: UserRole[];
  onDelete?: () => void;
  isEditing?: boolean;
  authUserId?: number;
  userId?: number;
  handleRoleChange?: (
    roleId: string,
    checked: boolean,
    isRoleDisabled: (roleId: string) => boolean,
  ) => void;
  handleRoleKeyDown?: (
    event: React.KeyboardEvent<HTMLDivElement>,
    roleId: string,
    checked: boolean,
    isRoleDisabled: (roleId: string) => boolean,
  ) => void;
}

// Roles de alto privilegio que requieren manejo especial
const PRIVILEGED_ROLES = new Set(['ADMIN', 'DEV']);

const UserForm: React.FC<UserFormProps> = ({
  form,
  onSubmit,
  submitButtonText,
  availableRoles,
  onDelete,
  isEditing = false,
  authUserId,
  userId,
  handleRoleChange,
  handleRoleKeyDown,
}) => {
  // Estado para mostrar/ocultar detalles de fortaleza de contraseña
  const [showPasswordDetails, setShowPasswordDetails] = useState(false);

  // Verificar si el usuario actual está editando su propio perfil
  const isEditingSelf = isEditing && authUserId === userId;

  // Verificar si el usuario tiene roles privilegiados
  const hasPrivilegedRole = form.data.roles.some((roleId) => {
    const role = availableRoles.find((r) => String(r.id) === roleId);
    return role && PRIVILEGED_ROLES.has(role.name);
  });

  // Función para determinar si un rol debe estar deshabilitado
  const isRoleDisabled = (roleId: string): boolean => {
    const role = availableRoles.find((r) => String(r.id) === roleId);
    if (!role) return true;

    // Si es un rol privilegiado y no lo tiene asignado, está deshabilitado
    if (PRIVILEGED_ROLES.has(role.name) && !form.data.roles.includes(String(role.id))) {
      return true;
    }

    // Si el usuario está editando su propio perfil y tiene un rol privilegiado, todos los roles están deshabilitados
    return isEditingSelf && hasPrivilegedRole;
  };

  const localHandleRoleChange = (roleId: string, checked: boolean) => {
    if (handleRoleChange) {
      handleRoleChange(roleId, checked, isRoleDisabled);
    } else {
      // Obtener el rol por ID
      const role = availableRoles.find((r) => String(r.id) === roleId);

      // Si es un rol privilegiado, no permitir cambios
      if (role && PRIVILEGED_ROLES.has(role.name)) {
        // Si ya tiene el rol, no permitir quitarlo
        if (form.data.roles.includes(roleId)) {
          return;
        }
        // Si no lo tiene, no permitir agregarlo
        return;
      }

      // Si el usuario está editando su propio perfil y tiene un rol privilegiado, no permitir cambios
      if (isEditingSelf && hasPrivilegedRole) {
        return;
      }

      const updatedRoles: string[] = checked
        ? [...form.data.roles, roleId]
        : form.data.roles.filter((id) => id !== roleId);

      form.setData('roles', updatedRoles);
    }
  };

  const localHandleKeyDown = (
    event: React.KeyboardEvent<HTMLDivElement>,
    roleId: string,
    checked: boolean,
  ) => {
    if (handleRoleKeyDown) {
      handleRoleKeyDown(event, roleId, checked, isRoleDisabled);
    } else if (event.key === 'Enter' || event.key === ' ') {
      event.preventDefault();
      localHandleRoleChange(roleId, !checked);
    }
  };

  const handleSubmit = (event: React.FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    onSubmit(form.data);
  };

  // Función para alternar la visualización de detalles de contraseña
  const togglePasswordDetails = () => {
    setShowPasswordDetails(!showPasswordDetails);
  };

  return (
    <div className="mx-auto w-full max-w-3xl">
      <form onSubmit={handleSubmit} className="space-y-10">
        {/* Sección de Información Personal */}
        <div>
          <h3 className="text-foreground mb-6 text-lg font-medium">Información Personal</h3>
          <div className="grid gap-6 md:grid-cols-2">
            <div className="space-y-2">
              <Label htmlFor="name" className="text-muted-foreground text-sm font-normal">
                Nombre Completo <span className="text-red-500">*</span>
              </Label>
              <Input
                id="name"
                type="text"
                value={form.data.name}
                onChange={(e) => {
                  form.setData('name', e.target.value);
                }}
                placeholder="Introduce el nombre completo"
                aria-invalid={!!form.errors['name']}
                className={`bg-muted/40 border-input border-b-accent-foreground/50 h-11 border px-4 focus-visible:ring-1 focus-visible:ring-offset-0 ${
                  form.errors['name'] ? 'border-red-500 ring-1 ring-red-500' : ''
                }`}
              />
              {form.errors['name'] && (
                <div className="mt-1 flex items-center gap-1 text-sm text-red-500">
                  <AlertCircle className="h-4 w-4" />
                  <span>{form.errors['name']}</span>
                </div>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="email" className="text-muted-foreground text-sm font-normal">
                Correo Electrónico <span className="text-red-500">*</span>
              </Label>
              <Input
                id="email"
                type="email"
                value={form.data.email}
                onChange={(e) => {
                  form.setData('email', e.target.value);
                }}
                placeholder="Introduce el correo electrónico"
                aria-invalid={!!form.errors['email']}
                className={`bg-muted/40 border-input border-b-accent-foreground/50 h-11 border px-4 focus-visible:ring-1 focus-visible:ring-offset-0 ${
                  form.errors['email'] ? 'border-red-500 ring-1 ring-red-500' : ''
                }`}
              />
              {form.errors['email'] && (
                <div className="mt-1 flex items-center gap-1 text-sm text-red-500">
                  <AlertCircle className="h-4 w-4" />
                  <span>{form.errors['email']}</span>
                </div>
              )}
            </div>

            {/* Checkbox para verificación automática de email */}
            {!isEditing && (
              <div className="mt-4">
                <div className="flex items-center space-x-2">
                  <Checkbox
                    id="auto_verify_email"
                    checked={form.data.auto_verify_email}
                    onCheckedChange={(checked) => {
                      form.setData('auto_verify_email', checked === true);
                    }}
                  />
                  <Label htmlFor="auto_verify_email" className="cursor-pointer text-sm font-normal">
                    Verificar email automáticamente
                  </Label>
                </div>
                <p className="text-muted-foreground mt-1 ml-6 text-xs">
                  Si está marcado, el usuario no necesitará verificar su email manualmente.
                </p>
              </div>
            )}
          </div>
        </div>

        {/* Sección de Contraseña */}
        {!isEditing && (
          <div>
            <h3 className="text-foreground mb-6 flex items-center text-lg font-medium">
              <span>Contraseña</span>
              <button
                type="button"
                onClick={togglePasswordDetails}
                className="ml-2 text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
              >
                {showPasswordDetails ? 'Ocultar detalles' : 'Mostrar detalles'}
              </button>
            </h3>
            <div className="grid gap-6 md:grid-cols-2">
              <PasswordField
                id="password"
                label="Contraseña"
                value={form.data.password}
                onChange={(newPassword) => {
                  form.setData('password', newPassword);
                }}
                error={form.errors['password'] ?? ''}
                required
                showStrengthIndicator
                showStrengthDetails={showPasswordDetails}
                showGenerateButton
              />

              <PasswordField
                id="password_confirmation"
                label="Confirmar Contraseña"
                value={form.data.password_confirmation}
                onChange={(newPassword) => {
                  form.setData('password_confirmation', newPassword);
                }}
                error={form.errors['password_confirmation'] ?? ''}
                required
                showStrengthIndicator={false}
                showGenerateButton={false}
              />
            </div>
          </div>
        )}

        {/* Sección de Roles */}
        <div>
          <h3 className="text-foreground mb-6 text-lg font-medium">Roles y Permisos</h3>
          <div>
            <p className="text-muted-foreground mb-4 text-sm">
              Selecciona los roles que deseas asignar a este usuario
            </p>

            {/* Mensaje informativo para roles privilegiados */}
            {isEditingSelf && hasPrivilegedRole && (
              <div className="mb-4 rounded-md border border-gray-300 bg-gray-50 p-3 text-sm text-gray-800 dark:border-gray-800 dark:bg-gray-900/20 dark:text-gray-400">
                No puedes modificar tus propios roles al tener privilegios de administrador.
              </div>
            )}

            {/* Mensaje si no hay roles disponibles */}
            {availableRoles.length === 0 && (
              <div className="mb-4 rounded-md border border-yellow-300 bg-yellow-50 p-3 text-sm text-yellow-800 dark:border-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400">
                No se han cargado roles. Verifica la conexión con el backend.
              </div>
            )}

            <div className="grid gap-2 md:grid-cols-3 lg:grid-cols-4">
              {availableRoles.map((role) => {
                // Verificar que el rol tenga un ID válido
                if (!role.id) {
                  return null;
                }

                const roleId = String(role.id);
                const isSelected = form.data.roles.includes(roleId);
                const isDisabled = isRoleDisabled(roleId);

                return (
                  <TooltipProvider key={roleId}>
                    <Tooltip>
                      <TooltipTrigger asChild>
                        <div
                          role="button"
                          tabIndex={isDisabled ? -1 : 0}
                          aria-pressed={isSelected}
                          aria-disabled={isDisabled}
                          onClick={() => {
                            if (!isDisabled) {
                              localHandleRoleChange(roleId, !isSelected);
                            }
                          }}
                          onKeyDown={(e) => {
                            if (!isDisabled) {
                              localHandleKeyDown(e, roleId, isSelected);
                            }
                          }}
                          className={`flex cursor-pointer items-center rounded-md border p-2 transition-all ${
                            isSelected
                              ? 'border-gray-500 bg-gray-50 dark:border-gray-500 dark:bg-gray-950/30'
                              : 'border-border hover:bg-muted/50 bg-transparent'
                          } ${isDisabled ? 'cursor-not-allowed opacity-60' : ''}`}
                        >
                          <div className="flex flex-1 items-center gap-2">
                            <div
                              className={`flex h-4 w-4 items-center justify-center rounded-sm border ${
                                isSelected
                                  ? 'border-gray-500 bg-gray-500 text-white'
                                  : 'border-muted-foreground/30'
                              }`}
                            >
                              {isSelected && <span className="text-[10px]">✓</span>}
                            </div>
                            <span className="text-sm font-medium">{role.name}</span>
                            {PRIVILEGED_ROLES.has(role.name) && (
                              <span className="ml-auto text-[10px] font-normal text-amber-600 dark:text-amber-400">
                                Privilegiado
                              </span>
                            )}
                          </div>
                        </div>
                      </TooltipTrigger>
                      <TooltipContent side="top" className="text-xs">
                        <p>
                          {typeof role['description'] === 'string'
                            ? role['description']
                            : `Rol de ${role.name}`}
                          {PRIVILEGED_ROLES.has(role.name) &&
                            ' (Este rol tiene privilegios especiales y no puede ser asignado manualmente)'}
                          {isEditingSelf &&
                            hasPrivilegedRole &&
                            ' (No puedes modificar tus propios roles al tener privilegios de administrador)'}
                        </p>
                      </TooltipContent>
                    </Tooltip>
                  </TooltipProvider>
                );
              })}
            </div>
            {form.errors['roles'] && (
              <div className="mt-2 flex items-center gap-1 text-sm text-red-500">
                <AlertCircle className="h-4 w-4" />
                <span>{form.errors['roles']}</span>
              </div>
            )}
          </div>
        </div>

        <div className="border-border flex flex-col justify-between gap-4 border-t pt-6 sm:flex-row">
          <Button
            type="submit"
            disabled={form.processing}
            className="h-11 rounded-md bg-black font-medium text-white hover:bg-black/90 dark:bg-white dark:text-black dark:hover:bg-white/90"
          >
            {form.processing ? 'Procesando...' : submitButtonText}
          </Button>

          {isEditing && onDelete && (
            <Button
              type="button"
              variant="outline"
              onClick={onDelete}
              disabled={hasPrivilegedRole} // Deshabilitar el botón de eliminar si el usuario tiene roles privilegiados
              className={`h-11 rounded-md border-red-300 text-red-600 hover:bg-red-50 hover:text-red-700 dark:border-red-800 dark:text-red-400 dark:hover:bg-red-950/50 ${
                hasPrivilegedRole ? 'cursor-not-allowed opacity-60' : ''
              }`}
            >
              Eliminar Usuario
            </Button>
          )}
        </div>
      </form>
    </div>
  );
};

export default UserForm;
