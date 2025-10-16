import { Button } from '@/components/ui/button';
import { useImageUpload } from '@/hooks/use-image-upload';
import { cn } from '@/utils/cn';
import { ImagePlus, Trash2, Upload, X } from 'lucide-react';
import { useCallback, useState } from 'react';

interface ImageUploadProps {
  onFileSelect: (file: File | null) => void;
  initialFile?: File | string | null;
  accept?: string;
}

const handleDragEvent = (e: React.DragEvent<HTMLDivElement>) => {
  e.preventDefault();
  e.stopPropagation();
};

export function ImageUpload({ onFileSelect, initialFile, accept }: Readonly<ImageUploadProps>) {
  const {
    previewUrl,
    fileName,
    fileInputRef,
    handleThumbnailClick,
    handleFileChange,
    handleRemove: baseHandleRemove,
  } = useImageUpload({
    onUpload: (_url: string, file: File) => {
      onFileSelect(file);
    },
    initialFile: initialFile ?? null,
  });

  const [isDragging, setIsDragging] = useState(false);

  const handleDragEnter = (e: React.DragEvent<HTMLDivElement>) => {
    handleDragEvent(e);
    setIsDragging(true);
  };

  const handleDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
    handleDragEvent(e);
    setIsDragging(false);
  };

  const handleDrop = useCallback(
    (e: React.DragEvent<HTMLDivElement>) => {
      handleDragEvent(e);
      setIsDragging(false);

      const file = e.dataTransfer.files[0];
      // If accept is provided, enforce by extension or mime
      if (file) {
        const allowAllImages = !accept || accept.includes('image/');
        const allowedList = (accept ?? 'image/*')
          .split(',')
          .map((s) => s.trim().toLowerCase())
          .filter(Boolean);
        const ext = file.name.split('.').pop()?.toLowerCase();
        const isMimeAllowed = allowedList.some((a) =>
          a.endsWith('/*') ? file.type.startsWith(a.replace('/*', '/')) : a === file.type,
        );
        const isExtAllowed = ext
          ? allowedList.some((a) => a.startsWith('.') && a === `.${ext}`)
          : false;
        if (!(allowAllImages || isMimeAllowed || isExtAllowed)) {
          return; // ignore drop if not allowed
        }
        const fakeEvent = {
          target: { files: [file] },
        } as unknown as React.ChangeEvent<HTMLInputElement>;
        handleFileChange(fakeEvent);
      }
    },
    [handleFileChange, accept],
  );

  const handleRemove = () => {
    baseHandleRemove();
    onFileSelect(null);
  };

  const hasPreview = !!previewUrl;

  return (
    <div className="w-full space-y-4">
      <input
        type="file"
        accept={accept ?? 'image/*'}
        className="hidden"
        ref={fileInputRef}
        onChange={handleFileChange}
      />

      {hasPreview ? (
        <div className="relative">
          <div className="group relative h-64 overflow-hidden rounded-lg border">
            <img
              src={previewUrl}
              alt="Preview"
              className="h-full w-full object-cover transition-transform duration-300 group-hover:scale-105"
            />
            <div className="absolute inset-0 bg-black/40 opacity-0 transition-opacity group-hover:opacity-100" />
            <div className="absolute inset-0 flex items-center justify-center gap-2 opacity-0 transition-opacity group-hover:opacity-100">
              <Button
                size="sm"
                variant="secondary"
                onClick={handleThumbnailClick}
                className="h-9 w-9 p-0"
              >
                <Upload className="h-4 w-4" />
              </Button>
              <Button
                size="sm"
                variant="destructive"
                onClick={handleRemove}
                className="h-9 w-9 p-0"
              >
                <Trash2 className="h-4 w-4" />
              </Button>
            </div>
          </div>
          {fileName && (
            <div className="text-muted-foreground mt-2 flex items-center gap-2 text-sm">
              <span className="truncate">{fileName}</span>
              <button
                type="button"
                onClick={handleRemove}
                className="hover:bg-muted ml-auto rounded-full p-1"
                aria-label="Remove file"
              >
                <X className="h-4 w-4" />
              </button>
            </div>
          )}
        </div>
      ) : (
        <div
          role="button"
          tabIndex={0}
          onClick={handleThumbnailClick}
          onKeyDown={(e) => {
            if (e.key === 'Enter') handleThumbnailClick();
          }}
          onDragOver={handleDragEvent}
          onDragEnter={handleDragEnter}
          onDragLeave={handleDragLeave}
          onDrop={handleDrop}
          className={cn(
            'border-muted-foreground/25 bg-muted/50 hover:bg-muted flex h-64 cursor-pointer flex-col items-center justify-center gap-4 rounded-lg border-2 border-dashed transition-colors',
            isDragging && 'border-primary/50 bg-primary/5',
          )}
        >
          <div className="bg-background rounded-full p-3 shadow-sm">
            <ImagePlus className="text-muted-foreground h-6 w-6" />
          </div>
          <div className="text-center">
            <p className="text-sm font-medium">Click para seleccionar</p>
            <p className="text-muted-foreground text-xs">o arrastra y suelta el archivo aquí</p>
          </div>
        </div>
      )}
    </div>
  );
}
