import { Button } from '@/components/ui/button';
import { type PageProps } from '@inertiajs/core';
import { Head, Link, usePage } from '@inertiajs/react';
import {
  AlertCircle,
  ArrowLeft,
  Ban,
  Clock,
  FileWarning,
  Home,
  Lock,
  ServerCrash,
  ShieldAlert,
  TimerOff,
  XCircle,
} from 'lucide-react';

interface ErrorPageProps extends PageProps {
  status: number;
  message: string;
}

export default function ErrorPage() {
  const props = usePage<ErrorPageProps>().props;
  const { status, message } = props;

  // Configurar el icono y el color según el tipo de error
  const getErrorConfig = () => {
    const errorConfigs = {
      server: {
        color: 'text-red-600 dark:text-red-400',
        bgColor: 'bg-red-50 dark:bg-red-950/20',
        accentColor: 'from-red-500/20 to-red-600/5',
      },
      client: {
        color: 'text-orange-600 dark:text-orange-400',
        bgColor: 'bg-orange-50 dark:bg-orange-950/20',
        accentColor: 'from-orange-500/20 to-orange-600/5',
      },
      auth: {
        color: 'text-yellow-600 dark:text-yellow-400',
        bgColor: 'bg-yellow-50 dark:bg-yellow-950/20',
        accentColor: 'from-yellow-500/20 to-yellow-600/5',
      },
      notFound: {
        color: 'text-blue-600 dark:text-blue-400',
        bgColor: 'bg-blue-50 dark:bg-blue-950/20',
        accentColor: 'from-blue-500/20 to-blue-600/5',
      },
      tooManyRequests: {
        color: 'text-amber-600 dark:text-amber-400',
        bgColor: 'bg-amber-50 dark:bg-amber-950/20',
        accentColor: 'from-amber-500/20 to-amber-600/5',
      },
      default: {
        Icon: AlertCircle,
        color: 'text-purple-600 dark:text-purple-400',
        bgColor: 'bg-purple-50 dark:bg-purple-950/20',
        accentColor: 'from-purple-500/20 to-purple-600/5',
        title: 'Error desconocido',
      },
    };

    // Determinar el tipo de error por rango de código
    switch (true) {
      // Errores del servidor (5xx)
      case status === 500: {
        return { ...errorConfigs.server, Icon: ServerCrash, title: 'Error interno del servidor' };
      }
      case status === 501: {
        return { ...errorConfigs.server, Icon: AlertCircle, title: 'No implementado' };
      }
      case status === 502: {
        return { ...errorConfigs.server, Icon: ServerCrash, title: 'Error de puerta de enlace' };
      }
      case status === 503: {
        return { ...errorConfigs.server, Icon: ServerCrash, title: 'Servicio no disponible' };
      }
      case status === 504: {
        return { ...errorConfigs.server, Icon: TimerOff, title: 'Tiempo de espera agotado' };
      }
      case status >= 500: {
        return { ...errorConfigs.server, Icon: ServerCrash, title: 'Error del servidor' };
      }

      // Errores de cliente (4xx)
      case status === 400: {
        return { ...errorConfigs.client, Icon: XCircle, title: 'Solicitud incorrecta' };
      }
      case status === 401: {
        return { ...errorConfigs.auth, Icon: Lock, title: 'No autenticado' };
      }
      case status === 403: {
        return { ...errorConfigs.server, Icon: ShieldAlert, title: 'Acceso denegado' };
      }
      case status === 404: {
        return { ...errorConfigs.notFound, Icon: Ban, title: 'Página no encontrada' };
      }
      case status === 405: {
        return { ...errorConfigs.client, Icon: Ban, title: 'Método no permitido' };
      }
      case status === 408: {
        return { ...errorConfigs.auth, Icon: Clock, title: 'Tiempo de espera agotado' };
      }
      case status === 413: {
        return { ...errorConfigs.client, Icon: FileWarning, title: 'Contenido demasiado grande' };
      }
      case status === 419: {
        return { ...errorConfigs.client, Icon: Clock, title: 'Sesión expirada' };
      }
      case status === 422: {
        return { ...errorConfigs.client, Icon: FileWarning, title: 'Datos no válidos' };
      }
      case status === 429: {
        return { ...errorConfigs.tooManyRequests, Icon: Clock, title: 'Demasiadas solicitudes' };
      }
      case status >= 400: {
        return { ...errorConfigs.client, Icon: AlertCircle, title: 'Error de cliente' };
      }

      // Otros errores
      default: {
        return errorConfigs.default;
      }
    }
  };

  const { Icon, color, bgColor, accentColor, title } = getErrorConfig();

  // Determinar la ruta de inicio según autenticación
  const getHomeRoute = () => {
    if (props.auth.user) {
      return route('internal.dashboard');
    }
    return route('welcome');
  };

  return (
    <div className={`flex min-h-screen w-full flex-col ${bgColor}`}>
      <Head title={`${status} - ${title}`} />

      {/* Gradiente superior */}
      <div
        className={`absolute inset-0 bg-gradient-to-b ${accentColor} pointer-events-none opacity-80`}
      />

      {/* Patrón de fondo */}
      <div className="bg-grid-black/[0.03] dark:bg-grid-white/[0.03] pointer-events-none absolute inset-0" />

      {/* Número de estado grande en el fondo */}
      <div className="pointer-events-none fixed inset-0 flex items-center justify-center opacity-[0.04] select-none dark:opacity-[0.07]">
        <span className="text-foreground text-[50vmin] font-black">{status}</span>
      </div>

      {/* Contenido principal */}
      <main className="relative flex flex-1 flex-col items-center justify-center px-4 py-20">
        <div className="mx-auto max-w-3xl text-center">
          {/* Icono en círculo */}
          <div
            className={`mx-auto mb-6 flex h-24 w-24 items-center justify-center rounded-full border border-white/50 bg-white/80 shadow-xl backdrop-blur-sm dark:border-gray-800/50 dark:bg-gray-900/80`}
          >
            <Icon className={`h-12 w-12 ${color}`} />
          </div>

          {/* Código de estado */}
          <h1 className={`mb-2 text-7xl font-extrabold tracking-tighter ${color}`}>{status}</h1>

          {/* Título del error */}
          <h2 className="text-foreground mb-6 text-3xl font-bold dark:text-gray-200">{title}</h2>

          {/* Mensaje */}
          <p className="text-muted-foreground mx-auto mb-12 max-w-xl text-xl dark:text-gray-400">
            {message}
          </p>

          {/* Botones */}
          <div className="flex flex-col justify-center gap-4 sm:flex-row sm:gap-6">
            <Button
              variant="default"
              size="lg"
              onClick={() => {
                globalThis.history.back();
              }}
              className="h-12 gap-2 rounded-full px-6 shadow-lg"
            >
              <ArrowLeft className="h-5 w-5" />
              Volver Atrás
            </Button>
            <Button
              variant="outline"
              size="lg"
              asChild
              className="h-12 gap-2 rounded-full bg-white/70 px-6 shadow-lg backdrop-blur-sm hover:bg-white/90 dark:bg-gray-900/70 dark:hover:bg-gray-800/90"
            >
              <Link href={getHomeRoute()}>
                <Home className="h-5 w-5" />
                Ir al Inicio
              </Link>
            </Button>
          </div>
        </div>
      </main>
    </div>
  );
}
