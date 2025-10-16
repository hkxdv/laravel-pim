import { generatePassword, generateSecurePassword } from '@/utils/password-generator';
import { useState } from 'react';

export interface UsePasswordOptions {
  /**
   * Valor inicial de la contraseña
   */
  initialPassword?: string;
  /**
   * Opciones para la generación de contraseñas
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
 * Hook personalizado para manejar la funcionalidad relacionada con contraseñas
 *
 * @param options - Opciones del hook
 * @returns Métodos y propiedades para manejar contraseñas
 */
export function usePassword(options: UsePasswordOptions = {}) {
  const { initialPassword = '', passwordOptions } = options;

  // Estado para la contraseña
  const [password, setPassword] = useState(initialPassword);

  // Estado para la visibilidad de la contraseña
  const [showPassword, setShowPassword] = useState(false);

  /**
   * Genera una nueva contraseña con opciones personalizadas
   * @param customOptions - Opciones personalizadas (opcional, anula las opciones predeterminadas)
   * @returns La contraseña generada
   */
  const generatePasswordWithOptions = (customOptions = passwordOptions) => {
    const newPassword = generatePassword(customOptions);
    setPassword(newPassword);
    return newPassword;
  };

  /**
   * Genera una nueva contraseña segura con la configuración por defecto
   * @returns La contraseña generada
   */
  const generateDefaultPassword = () => {
    const newPassword = generateSecurePassword();
    setPassword(newPassword);
    return newPassword;
  };

  /**
   * Alternar la visibilidad de la contraseña
   */
  const togglePasswordVisibility = () => {
    setShowPassword(!showPassword);
  };

  return {
    // Estado
    password,
    showPassword,

    // Setters
    setPassword,
    setShowPassword,

    // Acciones
    generatePasswordWithOptions,
    generateDefaultPassword,
    togglePasswordVisibility,

    // Propiedades para entrada de contraseña
    inputProps: {
      type: showPassword ? 'text' : 'password',
      value: password,
      onChange: (e: React.ChangeEvent<HTMLInputElement>) => {
        setPassword(e.target.value);
      },
    },
  };
}

export default usePassword;
