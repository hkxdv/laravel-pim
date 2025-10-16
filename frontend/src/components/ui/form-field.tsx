import { cn } from '@/utils/cn';
import type { HTMLAttributes, ReactNode } from 'react';
import { Label } from './label';

interface FormFieldProps extends HTMLAttributes<HTMLDivElement> {
  name: string;
  label: string;
  errorMessage?: string;
  children: ReactNode;
}

export function FormField({
  name,
  label,
  errorMessage,
  children,
  className,
}: Readonly<FormFieldProps>) {
  return (
    <div className={cn('space-y-2', className)}>
      <Label htmlFor={name}>{label}</Label>
      {children}
      {errorMessage && <p className="mt-2 text-sm text-red-600">{errorMessage}</p>}
    </div>
  );
}
