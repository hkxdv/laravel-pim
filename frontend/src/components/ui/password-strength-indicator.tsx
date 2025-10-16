/* eslint-disable sonarjs/no-duplicate-string */
import { evaluatePasswordStrength, getPasswordStrengthLabel } from '@/utils/password-generator';
import { Check, Shield, ShieldAlert, ShieldQuestion } from 'lucide-react';
import React from 'react';

interface PasswordStrengthIndicatorProps {
  password: string;
  showLabel?: boolean;
  showDetails?: boolean;
  className?: string;
}

/**
 * Componente que muestra un indicador visual de la fortaleza de una contraseña
 *
 * @param props - Props del componente
 * @returns Componente de indicador de fortaleza
 */
export const PasswordStrengthIndicator: React.FC<PasswordStrengthIndicatorProps> = ({
  password,
  showLabel = true,
  showDetails = false,
  className = '',
}) => {
  // No mostrar nada si no hay contraseña
  if (!password) return null;

  // Calcular la fortaleza de la contraseña
  const strength = evaluatePasswordStrength(password);
  const strengthLabel = getPasswordStrengthLabel(password);

  const strengthStyles = {
    weak: {
      color: 'text-red-500',
      bgColor: 'bg-red-500',
      icon: <ShieldAlert className="h-4 w-4 text-red-500" />,
    },
    medium: {
      color: 'text-amber-500',
      bgColor: 'bg-amber-500',
      icon: <ShieldQuestion className="h-4 w-4 text-amber-500" />,
    },
    strong: {
      color: 'text-lime-600',
      bgColor: 'bg-lime-500',
      icon: <Shield className="h-4 w-4 text-lime-500" />,
    },
    veryStrong: {
      color: 'text-green-600',
      bgColor: 'bg-green-500',
      icon: <Check className="h-4 w-4 text-green-500" />,
    },
  };

  const getStrengthStyle = () => {
    if (strength < 0.3) return strengthStyles.weak;
    if (strength < 0.6) return strengthStyles.medium;
    if (strength < 0.8) return strengthStyles.strong;
    return strengthStyles.veryStrong;
  };

  const { color, bgColor, icon } = getStrengthStyle();

  // Evaluar criterios específicos
  const hasMinLength = password.length >= 8;
  const hasUppercase = /[A-Z]/.test(password);
  const hasLowercase = /[a-z]/.test(password);
  const hasNumbers = /\d/.test(password);
  const hasSpecialChars = /[^a-zA-Z0-9]/.test(password);

  return (
    <div className={`mt-2 space-y-2 ${className}`}>
      <div className="flex items-center justify-between">
        <div className="flex-1">
          <div className="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
            <div
              className={`h-full ${bgColor} transition-all duration-500 ease-in-out`}
              style={{ width: `${strength * 100}%` }}
            />
          </div>
          {/* Segmentos de la barra */}
          <div className="mt-1 flex w-full justify-between px-0.5">
            <div className="h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></div>
            <div className="h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></div>
            <div className="h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></div>
            <div className="h-1 w-1 rounded-full bg-gray-300 dark:bg-gray-600"></div>
          </div>
        </div>
        {showLabel && (
          <div className="ml-3 flex items-center gap-1.5">
            {icon}
            <span className={`text-xs font-medium ${color}`}>{strengthLabel}</span>
          </div>
        )}
      </div>

      {/* Criterios detallados - visible solo si showDetails es true */}
      {showDetails && (
        <div className="text-muted-foreground grid grid-cols-2 gap-x-4 gap-y-1 pt-1 text-xs">
          <div className={`flex items-center gap-1 ${hasMinLength ? 'text-green-600' : ''}`}>
            <div
              className={`h-1.5 w-1.5 rounded-full ${hasMinLength ? 'bg-green-500' : 'bg-gray-300'}`}
            ></div>
            <span>Mínimo 8 caracteres</span>
          </div>
          <div className={`flex items-center gap-1 ${hasUppercase ? 'text-green-600' : ''}`}>
            <div
              className={`h-1.5 w-1.5 rounded-full ${hasUppercase ? 'bg-green-500' : 'bg-gray-300'}`}
            ></div>
            <span>Mayúsculas (A-Z)</span>
          </div>
          <div className={`flex items-center gap-1 ${hasLowercase ? 'text-green-600' : ''}`}>
            <div
              className={`h-1.5 w-1.5 rounded-full ${hasLowercase ? 'bg-green-500' : 'bg-gray-300'}`}
            ></div>
            <span>Minúsculas (a-z)</span>
          </div>
          <div className={`flex items-center gap-1 ${hasNumbers ? 'text-green-600' : ''}`}>
            <div
              className={`h-1.5 w-1.5 rounded-full ${hasNumbers ? 'bg-green-500' : 'bg-gray-300'}`}
            ></div>
            <span>Números (0-9)</span>
          </div>
          <div className={`flex items-center gap-1 ${hasSpecialChars ? 'text-green-600' : ''}`}>
            <div
              className={`h-1.5 w-1.5 rounded-full ${hasSpecialChars ? 'bg-green-500' : 'bg-gray-300'}`}
            ></div>
            <span>Caracteres especiales</span>
          </div>
          <div
            className={`flex items-center gap-1 ${password.length >= 12 ? 'text-green-600' : ''}`}
          >
            <div
              className={`h-1.5 w-1.5 rounded-full ${password.length >= 12 ? 'bg-green-500' : 'bg-gray-300'}`}
            ></div>
            <span>Recomendado: 12+ caracteres</span>
          </div>
        </div>
      )}
    </div>
  );
};

export default PasswordStrengthIndicator;
