import { type ModuleHeaderProps } from './interfaces';

/**
 * Componente para mostrar el encabezado estándar de un módulo.
 * Incluye título, descripción y saludo al usuario.
 */
export function ModuleHeader({
  title,
  description,
  userName,
  showGreeting = true,
}: Readonly<ModuleHeaderProps>) {
  return (
    <div className="mb-8">
      <h1 className="text-foreground text-3xl font-bold tracking-tight">{title}</h1>
      <p className="text-muted-foreground mt-2 text-lg">
        {showGreeting ? `Bienvenido, ${userName}. ` : ''}
        {description}
      </p>
    </div>
  );
}
