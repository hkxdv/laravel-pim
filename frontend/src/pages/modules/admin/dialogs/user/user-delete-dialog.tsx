import { ConfirmationDialog } from '@/components/dialogs/confirmation-dialog';
import type { UserIdentifier } from '../../interfaces';

/**
 * Define las props para el componente `UserDeleteDialog`.
 */
interface UserDeleteDialogProps {
  /** Controla si el diálogo está visible o no. */
  isDialogVisible: boolean;
  /** Función que se llama cuando se intenta cerrar el diálogo. */
  onDialogClose: () => void;
  /** El objeto del usuario que se va a eliminar, o null si no hay ninguno. */
  userTargetedForDelete: UserIdentifier | null;
  /** Función que se ejecuta cuando el usuario confirma la acción de eliminación. */
  onConfirmAction: () => void;
  /** Indica si la operación de eliminación está actualmente en curso. */
  isActionInProgress: boolean;
}

/**
 * Un componente de diálogo modal que pide al usuario confirmación antes de eliminar
 * a otro usuario de forma permanente. Muestra el nombre y el correo electrónico del
 * usuario afectado para evitar errores.
 *
 * @param props - Las props para configurar el diálogo.
 * @returns Un componente de diálogo de confirmación.
 */
export default function UserDeleteDialog({
  isDialogVisible,
  onDialogClose,
  userTargetedForDelete,
  onConfirmAction,
  isActionInProgress,
}: Readonly<UserDeleteDialogProps>) {
  return (
    <ConfirmationDialog
      isOpen={isDialogVisible}
      onClose={onDialogClose}
      onConfirm={onConfirmAction}
      isConfirming={isActionInProgress}
      variant="destructive"
      title="¿Estás completamente seguro?"
      description={
        <>
          Esta acción es irreversible y eliminará permanentemente al usuario «
          <strong>{userTargetedForDelete?.name}</strong>» ({userTargetedForDelete?.email}). No
          podrás deshacer esta acción.
        </>
      }
      confirmText={isActionInProgress ? 'Eliminando...' : 'Sí, eliminar usuario'}
    />
  );
}
