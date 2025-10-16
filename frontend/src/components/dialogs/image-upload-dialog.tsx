import { ImageUpload } from '@/components/image-upload';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { useState } from 'react';

interface ImageUploadDialogProps {
  open: boolean;
  onOpenChange: (open: boolean) => void;
  onFileSelect: (file: File | null) => void;
  title?: string;
  description?: string;
  initialFile?: File | string | null;
  accept?: string;
}

export function ImageUploadDialog({
  open,
  onOpenChange,
  onFileSelect,
  title = 'Subir imagen',
  description = 'Selecciona una imagen para subir',
  initialFile,
  accept,
}: Readonly<ImageUploadDialogProps>) {
  const [selectedFile, setSelectedFile] = useState<File | null>(null);

  const handleConfirm = () => {
    onFileSelect(selectedFile);
    onOpenChange(false);
  };

  const handleCancel = () => {
    onOpenChange(false);
  };

  return (
    <Dialog open={open} onOpenChange={onOpenChange}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{title}</DialogTitle>
          <DialogDescription>{description}</DialogDescription>
        </DialogHeader>
        <div className="py-4">
          <ImageUpload
            onFileSelect={setSelectedFile}
            initialFile={initialFile ?? null}
            accept={accept ?? 'image/*'}
          />
        </div>
        <DialogFooter className="flex-col-reverse gap-2 sm:flex-row sm:justify-end">
          <Button variant="outline" onClick={handleCancel}>
            Cancelar
          </Button>
          <Button onClick={handleConfirm} disabled={!selectedFile}>
            Aceptar
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
