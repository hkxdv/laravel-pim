import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { type ComponentType } from 'react';

export const appName = import.meta.env.VITE_APP_NAME;

if (!appName) {
  throw new Error('VITE_APP_NAME is not defined');
}

/**
 * Resuelve de forma asíncrona un componente de página de React para Inertia.js.
 * Utiliza la función de ayuda de `laravel-vite-plugin` para cargar dinámicamente
 * los componentes de página basados en su nombre desde el directorio `../pages`.
 *
 * @param name - El nombre del componente de página a resolver (p. ej., 'Auth/Login').
 * @returns Una promesa que se resuelve con el componente de React correspondiente.
 */
export const resolvePage = (name: string): Promise<ComponentType> => {
  return resolvePageComponent(
    `../pages/${name}.tsx`,
    import.meta.glob('../pages/**/*.tsx'),
  ) as Promise<ComponentType>;
};

/**
 * Crea un título de página estandarizado, combinando un título específico con el nombre de la aplicación.
 *
 * @param title - El título específico de la página actual.
 * @returns Una cadena de texto con el formato "Título Específico | Nombre de la App".
 */
export const createTitle = (title: string): string => `${title} | ${appName}`;
