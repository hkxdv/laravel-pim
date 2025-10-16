import { Label } from '@/components/ui/label';
import { cn } from '@/utils/cn';
import type { AnyFieldApi } from '@tanstack/react-form';
import React from 'react';

interface FormFieldProps<T extends AnyFieldApi> {
  field: T;
  label: string;
  children: React.ReactNode;
  className?: string;
  labelClassName?: string;
}

const getErrorMessage = (error: unknown): string | null => {
  if (!error) return null;
  if (typeof error === 'string') return error;
  if (
    typeof error === 'object' &&
    'message' in error &&
    typeof (error as { message: unknown }).message === 'string'
  ) {
    return (error as { message: string }).message;
  }
  return 'Error de validación';
};

export function FormField<T extends AnyFieldApi>({
  field,
  label,
  children,
  className,
  labelClassName,
}: Readonly<FormFieldProps<T>>) {
  const errorMessage = getErrorMessage(field.state.meta.errors[0]);

  return (
    <div className={cn('grid w-full items-center gap-1.5', className)}>
      <Label
        htmlFor={field.name}
        className={cn(errorMessage ? 'text-destructive' : '', labelClassName)}
      >
        {label}
      </Label>
      {children}
      {errorMessage && (
        <p role="alert" className="text-destructive text-sm font-medium">
          {errorMessage}
        </p>
      )}
    </div>
  );
}
