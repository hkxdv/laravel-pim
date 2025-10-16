import RowActionsMenu from '@/components/data/row-actions-menu';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import UserDeleteDialog from '@/pages/modules/admin/dialogs/user/user-delete-dialog';
import type { StaffUser } from '@/types';
import { router } from '@inertiajs/react';
import type { Row } from '@tanstack/react-table';
import { Pencil, Trash } from 'lucide-react';
import { useCallback, useState } from 'react';

interface UserActionsCellProps {
  row: Row<StaffUser>;
  authUserId: number;
}

export function UserActionsCell({ row, authUserId }: Readonly<UserActionsCellProps>) {
  const [isDeleteDialogOpen, setIsDeleteDialogOpen] = useState(false);
  const [isDeleting, setIsDeleting] = useState(false);
  const { showSuccess, showError, showWarning } = useToastNotifications();

  const user = row.original;
  const isCurrentUser = user.id === authUserId;

  // Verificar si el usuario tiene rol de DEV o ADMIN
  const hasPrivilegedRole = user.roles?.some((role) =>
    ['DEV', 'ADMIN'].includes(role.name.toUpperCase()),
  );

  const isDevUser = user.roles?.some((role) => role.name.toUpperCase() === 'DEV');

  const handleEdit = useCallback(() => {
    // Si es un usuario DEV, mostrar advertencia y redirigir después de unos segundos
    if (isDevUser) {
      router.get(
        route('internal.admin.users.edit', user.id),
        {},
        {
          onSuccess: () => {
            showWarning('Acceso restringido. Editando usuario con privilegios de desarrollador.', {
              duration: 5000,
              description: 'Serás redirigido a la lista de usuarios en unos segundos.',
            });

            // Redirigir después de 5 segundos
            setTimeout(() => {
              router.get(route('internal.admin.users.index'));
            }, 5000);
          },
        },
      );
    } else {
      router.get(route('internal.admin.users.edit', user.id));
    }
  }, [user.id, isDevUser, showWarning]);

  const openDeleteDialog = useCallback(() => {
    setIsDeleteDialogOpen(true);
  }, []);

  const handleDelete = useCallback(() => {
    setIsDeleting(true);
    router.delete(route('internal.admin.users.destroy', user.id), {
      preserveScroll: true,
      onSuccess: () => {
        showSuccess('Usuario eliminado con éxito.');
      },
      onError: () => {
        showError('No se pudo eliminar el usuario.');
      },
      onFinish: () => {
        setIsDeleting(false);
        setIsDeleteDialogOpen(false);
      },
    });
  }, [user.id, showSuccess, showError]);

  // Función para obtener el texto del tooltip para el botón de eliminar
  const getDeleteTooltipText = () => {
    if (isCurrentUser) {
      return 'No puedes eliminar tu propio usuario';
    }
    if (hasPrivilegedRole) {
      return 'No se puede eliminar usuarios con privilegios especiales';
    }
    return 'Eliminar Usuario';
  };

  const deleteTooltipText = getDeleteTooltipText();

  return (
    <>
      <div className="flex justify-end pr-4">
        <RowActionsMenu
          idToCopy={user.id}
          items={[
            {
              key: 'edit',
              label: isDevUser ? 'Editar Usuario (Acceso restringido)' : 'Editar Usuario',
              icon: <Pencil className="h-4 w-4" />,
              onClick: handleEdit,
            },
            {
              key: 'delete',
              label: deleteTooltipText,
              icon: <Trash className="h-4 w-4" />,
              variant: 'destructive',
              onClick: openDeleteDialog,
              disabled: !!(isCurrentUser || hasPrivilegedRole),
            },
          ]}
        />
      </div>

      <UserDeleteDialog
        isDialogVisible={isDeleteDialogOpen}
        onDialogClose={() => {
          setIsDeleteDialogOpen(false);
        }}
        onConfirmAction={handleDelete}
        isActionInProgress={isDeleting}
        userTargetedForDelete={user}
      />
    </>
  );
}
