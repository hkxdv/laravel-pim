import { Button } from '@/components/ui/button';
import { cn } from '@/utils/cn';
import { CheckCircle, FileUp, XCircle } from 'lucide-react';
import React, { useRef, useState } from 'react';

interface CustomFileInputProps {
  id?: string;
  name?: string;
  value: File | null | string;
  onChange: (file: File | null) => void;
  onClick?: () => void;
  placeholder?: string;
  className?: string;
  disabled?: boolean;
  required?: boolean;
  accept?: string;
}

export const CustomFileInput = React.forwardRef<HTMLInputElement, CustomFileInputProps>(
  (
    { value, onChange, onClick, placeholder = 'Seleccionar archivo...', className, name, ...props },
    ref,
  ) => {
    const internalRef = useRef<HTMLInputElement>(null);
    const [fileName, setFileName] = useState<string>('');

    React.useEffect(() => {
      if (typeof value === 'string' && value) {
        setFileName(value.split('/').pop() ?? 'Archivo');
      } else if (value instanceof File) {
        setFileName(value.name);
      } else {
        setFileName('');
      }
    }, [value]);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
      const file = e.target.files?.[0] ?? null;
      setFileName(file?.name ?? '');
      onChange(file);
    };

    const handleButtonClick = () => {
      if (onClick) {
        onClick();
      } else {
        internalRef.current?.click();
      }
    };

    const handleClear = (e: React.MouseEvent<HTMLButtonElement>) => {
      e.stopPropagation();
      setFileName('');
      onChange(null);
      if (internalRef.current) {
        internalRef.current.value = '';
      }
    };

    const fileSelected = !!fileName;

    return (
      <div className={cn('flex w-full items-center gap-2', className)}>
        <input
          type="file"
          className="hidden"
          name={name}
          ref={(node) => {
            internalRef.current = node;
            if (typeof ref === 'function') {
              ref(node);
            } else if (ref) {
              ref.current = node;
            }
          }}
          onChange={handleFileChange}
          {...props}
        />
        <Button
          type="button"
          variant="outline"
          onClick={handleButtonClick}
          className="flex-grow"
          disabled={props.disabled}
        >
          <div className="flex w-full items-center justify-between">
            <div className="text-muted-foreground flex items-center gap-2">
              {fileSelected ? (
                <CheckCircle className="text-success h-5 w-5" />
              ) : (
                <FileUp className="h-5 w-5" />
              )}
              <span className="truncate" title={fileName || placeholder}>
                {fileName || placeholder}
              </span>
            </div>
          </div>
        </Button>
        {fileSelected && !props.disabled && (
          <Button
            type="button"
            variant="ghost"
            size="icon"
            onClick={handleClear}
            className="text-muted-foreground hover:text-destructive"
            aria-label="Limpiar archivo seleccionado"
          >
            <XCircle className="h-5 w-5" />
          </Button>
        )}
      </div>
    );
  },
);

CustomFileInput.displayName = 'CustomFileInput';
