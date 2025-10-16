import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import path from 'node:path';
import { defineConfig } from 'vite';

export default defineConfig(({ mode }) => {
  const ProcessEnv: NodeJS.ProcessEnv = process.env;
  const isProduction = mode === 'production';
  const isDocker = ProcessEnv['APP_RUNNING_IN_CONTAINER'] === 'true';
  const host = isProduction || isDocker ? '0.0.0.0' : 'localhost';

  // Obtener la URL de la aplicación desde las variables de entorno para HMR
  const appUrl = ProcessEnv['VITE_APP_URL'] ?? 'http://localhost:8080';
  // Extraer el hostname de la URL para usarlo en la configuración de HMR
  const appHostname = new URL(appUrl).hostname;

  return {
    envDir: '../',
    server: {
      host,
      port: 5173,
      hmr: {
        // En test-producción o cuando Vite sirve para Docker/LAN, usar el hostname de APP_URL
        host: isProduction || isDocker ? appHostname : 'localhost',
      },
      watch: {
        usePolling: true,
      },
    },
    preview: {
      host,
      port: 5173,
    },
    build: {
      emptyOutDir: true,
    },
    plugins: [
      laravel({
        input: 'src/app.tsx',
        publicDirectory: '../backend/public',
        refresh: true,
      }),
      react(),
      tailwindcss(),
    ],
    resolve: {
      alias: {
        '@': path.resolve(import.meta.dirname, 'src'),
      },
    },
  };
});
