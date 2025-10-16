import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tooltip, TooltipContent, TooltipProvider, TooltipTrigger } from '@/components/ui/tooltip';
import { usePassword } from '@/hooks/use-password';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import { Eye, EyeOff, Shuffle } from 'lucide-react';
import React, { useEffect, useRef } from 'react';
import PasswordStrengthIndicator from './password-strength-indicator';

export interface PasswordFieldProps {
  /**
   * Valor de la contraseña
   */
  value: string;

  /**
   * Función llamada cuando cambia la contraseña
   */
  onChange: (newPassword: string) => void;

  /**
   * ID del campo
   */
  id?: string;

  /**
   * Etiqueta del campo
   */
  label?: string;

  /**
   * Texto de placeholder
   */
  placeholder?: string;

  /**
   * Si el campo es requerido
   */
  required?: boolean;

  /**
   * Si se debe mostrar el indicador de fortaleza
   */
  showStrengthIndicator?: boolean;

  /**
   * Si se debe mostrar los detalles de criterios de fortaleza
   */
  showStrengthDetails?: boolean;

  /**
   * Si se debe mostrar el botón para generar contraseña
   */
  showGenerateButton?: boolean;

  /**
   * Si hay un error en el campo
   */
  error?: string;

  /**
   * Clases CSS adicionales
   */
  className?: string;

  /**
   * Opciones adicionales para el hook de contraseña
   */
  passwordOptions?: {
    length?: number;
    includeLowercase?: boolean;
    includeUppercase?: boolean;
    includeNumbers?: boolean;
    includeSymbols?: boolean;
  };
}

/**
 * Componente para campos de contraseña con funciones adicionales
 * como mostrar/ocultar, generar contraseña e indicador de fortaleza
 */
export const PasswordField: React.FC<PasswordFieldProps> = ({
  value,
  onChange,
  id = 'password',
  label = 'Contraseña',
  placeholder = '••••••••',
  required = false,
  showStrengthIndicator = true,
  showStrengthDetails = false,
  showGenerateButton = true,
  error,
  className = '',
  passwordOptions,
}) => {
  // Usar una referencia para evitar actualizaciones infinitas
  const internalValueRef = useRef(value);

  // Usar el hook de notificaciones toast
  const { showSuccess } = useToastNotifications();

  const passwordHook = usePassword({
    initialPassword: value,
    passwordOptions: passwordOptions ?? {},
  });

  // Actualizar el valor interno solo cuando el prop value cambia
  useEffect(() => {
    // Solo actualizar si el valor interno es diferente del prop
    if (value !== internalValueRef.current) {
      internalValueRef.current = value;
      passwordHook.setPassword(value);
    }
  }, [value, passwordHook]);

  // Manejar cambios internos y propagarlos hacia arriba
  const handlePasswordChange = (newPassword: string) => {
    // Actualizar la referencia interna
    internalValueRef.current = newPassword;
    // Actualizar el estado local
    passwordHook.setPassword(newPassword);
    // Propagar el cambio hacia arriba
    onChange(newPassword);
  };

  // Manejar la generación de contraseña
  const handleGeneratePassword = () => {
    const newPassword = passwordHook.generateDefaultPassword();
    // Actualizar la referencia interna
    internalValueRef.current = newPassword;
    // Propagar el cambio hacia arriba
    onChange(newPassword);

    // Mostrar una notificación de éxito
    showSuccess('Contraseña segura generada correctamente');
  };

  // Función para copiar la contraseña al portapapeles
  const handleCopyPassword = () => {
    if (passwordHook.password) {
      navigator.clipboard
        .writeText(passwordHook.password)
        .then(() => {
          showSuccess('Contraseña copiada al portapapeles');
        })
        .catch((error: unknown) => {
          console.error('Error al copiar la contraseña:', error);
        });
    }
  };

  return (
    <div className={`space-y-2 ${className}`}>
      {label && (
        <Label htmlFor={id} className="text-muted-foreground text-sm font-normal">
          {label} {required && <span className="text-red-500">*</span>}
        </Label>
      )}

      <div className="relative flex items-center">
        <Input
          id={id}
          type={passwordHook.showPassword ? 'text' : 'password'}
          value={passwordHook.password}
          onChange={(e) => {
            handlePasswordChange(e.target.value);
          }}
          placeholder={placeholder}
          aria-invalid={!!error}
          className={`bg-muted/40 h-11 px-4 pr-16 focus-visible:ring-1 focus-visible:ring-offset-0 ${
            error ? 'border-red-500 ring-1 ring-red-500' : ''
          }`}
        />

        <div className="absolute right-2 flex gap-1">
          <TooltipProvider>
            <Tooltip>
              <TooltipTrigger asChild>
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  className="h-7 w-7 p-0"
                  onClick={passwordHook.togglePasswordVisibility}
                >
                  {passwordHook.showPassword ? (
                    <Eye className="h-3.5 w-3.5" />
                  ) : (
                    <EyeOff className="h-3.5 w-3.5" />
                  )}
                </Button>
              </TooltipTrigger>
              <TooltipContent>
                <p>{passwordHook.showPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'}</p>
              </TooltipContent>
            </Tooltip>
          </TooltipProvider>

          {showGenerateButton && (
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-7 w-7 p-0"
                    onClick={handleGeneratePassword}
                  >
                    <Shuffle className="h-3.5 w-3.5" />
                  </Button>
                </TooltipTrigger>
                <TooltipContent>
                  <p>Generar contraseña segura</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          )}

          {passwordHook.password && (
            <TooltipProvider>
              <Tooltip>
                <TooltipTrigger asChild>
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className="h-7 w-7 p-0"
                    onClick={handleCopyPassword}
                  >
                    <svg
                      xmlns="http://www.w3.org/2000/svg"
                      viewBox="0 0 24 24"
                      fill="none"
                      stroke="currentColor"
                      strokeWidth="2"
                      strokeLinecap="round"
                      strokeLinejoin="round"
                      className="h-3.5 w-3.5"
                    >
                      <rect width="14" height="14" x="8" y="8" rx="2" ry="2" />
                      <path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2" />
                    </svg>
                  </Button>
                </TooltipTrigger>
                <TooltipContent>
                  <p>Copiar contraseña</p>
                </TooltipContent>
              </Tooltip>
            </TooltipProvider>
          )}
        </div>
      </div>

      {/* Indicador de fortaleza de contraseña */}
      {showStrengthIndicator && passwordHook.password && (
        <PasswordStrengthIndicator
          password={passwordHook.password}
          showDetails={showStrengthDetails}
        />
      )}

      {/* Mensaje de error */}
      {error && (
        <div className="mt-1 flex items-center gap-1 text-sm text-red-500">
          <span>{error}</span>
        </div>
      )}
    </div>
  );
};

export default PasswordField;
