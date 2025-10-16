/**
 * Limpia el nombre de una clase de modelo de Eloquent para mostrarlo en la UI.
 * Elimina el namespace 'App\\Models\\' o 'Modules\\...\\Models\\'.
 *
 * @param fullClassName - El nombre completo de la clase (e.g., "App\\Models\\StaffUsers").
 * @returns El nombre de la clase limpio (e.g., "StaffUsers").
 */
export function getCleanClassName(fullClassName: string | null | undefined): string {
  if (!fullClassName) {
    return 'N/A';
  }
  // Se queda con la última parte del namespace
  const parts = fullClassName.split('\\');
  return parts.pop() ?? fullClassName;
}
