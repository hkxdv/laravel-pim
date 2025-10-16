import { ConfirmationDialog } from '@/components/dialogs/confirmation-dialog';
import HeadingSmall from '@/components/heading-small';
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function DeleteUser() {
  const [isConfirmingDelete, setIsConfirmingDelete] = useState(false);
  const { delete: destroy, processing } = useForm();

  const handleDeleteConfirm = () => {
    destroy(route('internal.settings.profile.destroy'), {
      preserveScroll: true,
      onSuccess: () => {
        setIsConfirmingDelete(false);
      },
      onError: () => {
        setIsConfirmingDelete(false);
      },
    });
  };

  return (
    <section className="space-y-6">
      <header>
        <HeadingSmall
          title="Eliminar cuenta"
          description="Elimina tu cuenta y todos sus recursos"
        />
        <p className="text-muted-foreground mt-1 text-sm">
          Una vez que tu cuenta sea eliminada, todos sus recursos y datos también serán eliminados
          permanentemente.
        </p>
      </header>

      <Button
        variant="destructive"
        onClick={() => {
          setIsConfirmingDelete(true);
        }}
      >
        Eliminar cuenta
      </Button>

      <ConfirmationDialog
        isOpen={isConfirmingDelete}
        onClose={() => {
          setIsConfirmingDelete(false);
        }}
        onConfirm={handleDeleteConfirm}
        title="¿Estás seguro de que quieres eliminar tu cuenta?"
        description="Una vez que tu cuenta sea eliminada, todos sus recursos y datos también serán eliminados permanentemente. Esta acción no se puede deshacer."
        isConfirming={processing}
        variant="destructive"
        confirmText={processing ? 'Eliminando...' : 'Eliminar cuenta'}
      />
    </section>
  );
}
