/**
 * Genera un número aleatorio seguro utilizando Crypto API cuando está disponible
 * @param max - El valor máximo (exclusivo)
 * @returns Un número aleatorio entre 0 (inclusivo) y max (exclusivo)
 */
export const getSecureRandom = (max: number): number => {
  // Validar rango de entrada para evitar resultados no definidos
  if (max <= 0) {
    return 0;
  }
  // Usar crypto.getRandomValues cuando esté disponible (navegadores modernos)
  if ('crypto' in globalThis) {
    const randomBuffer = new Uint32Array(1);
    globalThis.crypto.getRandomValues(randomBuffer);
    // Convertir el valor aleatorio a un número entre 0 y max-1
    const value = randomBuffer.at(0) ?? 0;
    return value % max;
  }
  // Fallback a Math.random si crypto API no está disponible
  // Nota: Este es un fallback seguro para este caso de uso específico
  // (generación de contraseñas para uso local)
  // eslint-disable-next-line sonarjs/pseudo-random
  return Math.floor(Math.random() * max);
};

/**
 * Selecciona un carácter aleatorio de una cadena
 * @param str - La cadena de la que seleccionar un carácter
 * @returns Un carácter aleatorio de la cadena
 */
export const getRandomChar = (str: string): string => {
  return str.charAt(getSecureRandom(str.length));
};

/**
 * Tipos de caracteres para usar en contraseñas
 */
export const PASSWORD_CHARS = {
  lowercase: 'abcdefghijklmnopqrstuvwxyz',
  uppercase: 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
  numbers: '0123456789',
  symbols: '@#$%&*!?',
};

/**
 * Opciones para la generación de contraseñas
 */
export interface PasswordOptions {
  /** Longitud de la contraseña (por defecto: 12) */
  length?: number;
  /** Incluir letras minúsculas (por defecto: true) */
  includeLowercase?: boolean;
  /** Incluir letras mayúsculas (por defecto: true) */
  includeUppercase?: boolean;
  /** Incluir números (por defecto: true) */
  includeNumbers?: boolean;
  /** Incluir símbolos (por defecto: true) */
  includeSymbols?: boolean;
}

/**
 * Genera una contraseña segura con una combinación de caracteres
 * @param options - Opciones para la generación de contraseñas
 * @returns Contraseña generada aleatoriamente
 */
export const generatePassword = (options?: PasswordOptions): string => {
  const {
    length = 12,
    includeLowercase = true,
    includeUppercase = true,
    includeNumbers = true,
    includeSymbols = true,
  } = options ?? {};

  // Verificar que al menos un conjunto de caracteres esté habilitado
  if (!includeLowercase && !includeUppercase && !includeNumbers && !includeSymbols) {
    throw new Error('Al menos un conjunto de caracteres debe estar habilitado');
  }

  // Construir el conjunto de caracteres basado en las opciones
  let allChars = '';
  const charSets: string[] = [];

  if (includeLowercase) {
    allChars += PASSWORD_CHARS.lowercase;
    charSets.push(PASSWORD_CHARS.lowercase);
  }

  if (includeUppercase) {
    allChars += PASSWORD_CHARS.uppercase;
    charSets.push(PASSWORD_CHARS.uppercase);
  }

  if (includeNumbers) {
    allChars += PASSWORD_CHARS.numbers;
    charSets.push(PASSWORD_CHARS.numbers);
  }

  if (includeSymbols) {
    allChars += PASSWORD_CHARS.symbols;
    charSets.push(PASSWORD_CHARS.symbols);
  }

  // Asegurarse de incluir al menos un carácter de cada conjunto habilitado
  let password = '';
  for (const charSet of charSets) {
    password += getRandomChar(charSet);
  }

  // Completar con caracteres aleatorios hasta alcanzar la longitud deseada
  for (let i = password.length; i < length; i++) {
    password += getRandomChar(allChars);
  }

  // Mezclar los caracteres para que la contraseña sea más aleatoria
  // Fisher-Yates shuffle
  // eslint-disable-next-line @typescript-eslint/no-misused-spread
  const chars: string[] = [...password];
  for (let i = chars.length - 1; i > 0; i--) {
    const j = getSecureRandom(i + 1);
    const a = chars[i];
    const b = chars[j];
    if (a !== undefined && b !== undefined) {
      chars[i] = b;
      chars[j] = a;
    }
  }

  return chars.join('');
};

/**
 * Genera una contraseña segura con la configuración predeterminada
 * (útil como atajo para la generación rápida de contraseñas)
 * @returns Contraseña segura generada
 */
export const generateSecurePassword = (): string => {
  return generatePassword();
};

/**
 * Evalúa la fortaleza de una contraseña
 * @param password - La contraseña a evaluar
 * @returns Un valor entre 0 (débil) y 1 (fuerte)
 */
export const evaluatePasswordStrength = (password: string): number => {
  if (!password) return 0;

  const length = password.length;

  // Factores de fortaleza
  let strength = 0;

  // Longitud (hasta un máximo de 16 caracteres)
  strength += Math.min(length / 16, 1) * 0.25;

  // Presencia de diferentes tipos de caracteres
  if (/[a-z]/.test(password)) strength += 0.15;
  if (/[A-Z]/.test(password)) strength += 0.2;
  if (/\d/.test(password)) strength += 0.2;
  if (/[^a-zA-Z0-9]/.test(password)) strength += 0.2;

  // Penalizar secuencias repetidas
  const repetitions = password.match(/(.)\1+/g)?.length ?? 0;
  strength -= repetitions * 0.05;

  // Limitar entre 0 y 1
  return Math.max(0, Math.min(strength, 1));
};

/**
 * Obtiene una descripción textual de la fortaleza de una contraseña
 * @param password - La contraseña a evaluar
 * @returns Descripción de la fortaleza ("Débil", "Media", "Buena", "Fuerte")
 */
export const getPasswordStrengthLabel = (password: string): string => {
  const strength = evaluatePasswordStrength(password);

  if (strength < 0.3) return 'Débil';
  if (strength < 0.6) return 'Media';
  if (strength < 0.8) return 'Buena';
  return 'Fuerte';
};
