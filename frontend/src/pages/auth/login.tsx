import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToastNotifications } from '@/hooks/use-toast-notifications';
import { Head, useForm } from '@inertiajs/react';
import { AlertCircle, LoaderCircle } from 'lucide-react';
import { type FormEventHandler, useCallback, useEffect, useRef, useState } from 'react';

interface LoginForm {
  email: string;
  password: string;
  remember: boolean;
}

interface LoginProps {
  status?: string;
  canResetPassword?: boolean;
  errors?: {
    email?: string;
    password?: string;
  };
  pageTitle?: string;
  formTitle?: string;
  formDescription?: string;
  emailFieldLabel?: string;
  emailFieldType?: 'email' | 'text';
  emailFieldPlaceholder?: string;
  emailFieldAutoComplete?: string;
  submitButtonText?: string;
  postUrl: string;
  forgotPasswordUrl?: string | null;
  blockquoteText?: string;
  blockquoteFooter?: string;
}

// Mensajes personalizados para claves de traducción comunes
const errorMessages: Record<string, string> = {
  'auth.failed': 'Las credenciales proporcionadas no coinciden con nuestros registros.',
  // eslint-disable-next-line sonarjs/no-hardcoded-passwords
  'auth.password': 'La contraseña ingresada es incorrecta.',
  'auth.throttle':
    'Demasiados intentos de inicio de sesión. Por favor, inténtalo de nuevo en unos segundos.',
  'auth.not_verified':
    'Tu cuenta de correo electrónico no ha sido verificada. Por favor, revisa tu bandeja de entrada para el enlace de verificación.',
};

// Función para obtener mensajes de error amigables
const getErrorMessage = (error?: string) => {
  if (!error) return '';
  return errorMessages[error] ?? error;
};

export default function Login({
  status,
  canResetPassword = false,
  errors: serverErrors,
  pageTitle,
  formTitle,
  formDescription,
  emailFieldLabel,
  emailFieldType = 'text',
  emailFieldPlaceholder,
  emailFieldAutoComplete = 'username',
  submitButtonText,
  postUrl,
  forgotPasswordUrl,
  blockquoteText,
  blockquoteFooter,
}: Readonly<LoginProps>) {
  const form = useForm<Required<LoginForm>>({
    email: '',
    password: '',
    remember: false,
  });

  const [, setLoginAttempts] = useState(0);
  const [isLocked, setIsLocked] = useState(false);
  const [lockCountdown, setLockCountdown] = useState(0);
  const shownErrors = useRef<Set<string>>(new Set());
  const { showSuccess, showError } = useToastNotifications();

  useEffect(() => {
    const currentShownErrors = shownErrors.current;
    return () => {
      currentShownErrors.clear();
    };
  }, []);

  useEffect(() => {
    let interval: NodeJS.Timeout;
    if (isLocked && lockCountdown > 0) {
      interval = setInterval(() => {
        setLockCountdown((prev) => {
          if (prev <= 1) {
            setIsLocked(false);
            setLoginAttempts(0);
            return 0;
          }
          return prev - 1;
        });
      }, 1000);
    }
    return () => {
      clearInterval(interval);
    };
  }, [isLocked, lockCountdown]);

  const showErrorToast = useCallback(
    (title: string, message: string, icon: React.ReactNode) => {
      if (shownErrors.current.has(message)) return;
      shownErrors.current.add(message);

      // Usar el hook y limpiar la marca después del tiempo de vida del toast
      showError(title, {
        icon,
        description: message,
        duration: 4000,
      });
      setTimeout(() => {
        shownErrors.current.delete(message);
      }, 4200);
    },
    [showError],
  );

  // Estabilizar el manejador de intentos fallidos usando actualización funcional
  const handleFailedAttempt = useCallback(
    (isThrottled: boolean) => {
      if (isThrottled) {
        setIsLocked(true);
        setLockCountdown(60);
        return;
      }
      setLoginAttempts((prev) => {
        const next = prev + 1;
        if (next >= 5) {
          setIsLocked(true);
          setLockCountdown(60);
        }
        return next;
      });
    },
    [setIsLocked, setLockCountdown, setLoginAttempts],
  );

  useEffect(() => {
    if (status) {
      showSuccess(status);
    }
  }, [showSuccess, status]);

  useEffect(() => {
    const errorKey = serverErrors?.email ?? serverErrors?.password;
    if (!errorKey) return;

    const errorMessage = getErrorMessage(errorKey);

    // Evitar repetir la lógica para el mismo mensaje de error
    if (shownErrors.current.has(errorMessage)) return;

    const isThrottled = errorKey === 'auth.throttle';
    handleFailedAttempt(isThrottled);
    showErrorToast('Error de inicio de sesión', errorMessage, <AlertCircle className="h-4 w-4" />);
  }, [serverErrors, handleFailedAttempt, showErrorToast]);

  const submit: FormEventHandler = (e) => {
    e.preventDefault();
    if (!form.data.email) {
      showErrorToast(
        'Campo requerido',
        'El correo electrónico es necesario para iniciar sesión',
        <AlertCircle className="h-4 w-4" />,
      );
      return;
    }
    if (!form.data.password) {
      showErrorToast(
        'Campo requerido',
        'La contraseña es necesaria para iniciar sesión',
        <AlertCircle className="h-4 w-4" />,
      );
      return;
    }
    if (form.data.password.length < 6) {
      showErrorToast(
        'Contraseña inválida',
        'La contraseña debe tener al menos 6 caracteres',
        <AlertCircle className="h-4 w-4" />,
      );
      return;
    }
    shownErrors.current.clear();
    form.post(postUrl, {
      onFinish: () => {
        form.reset('password');
      },
      preserveScroll: true,
      preserveState: true,
      onError: (errorResponse) => {
        const errorKey = errorResponse['email'] ?? errorResponse['password'];
        if (errorKey) {
          const isThrottled = errorKey === 'auth.throttle';
          handleFailedAttempt(isThrottled);
          const errorMessage = getErrorMessage(errorKey);
          showErrorToast(
            'Error de inicio de sesión',
            errorMessage,
            <AlertCircle className="h-4 w-4" />,
          );
        } else {
          showErrorToast(
            'Error inesperado',
            'Ocurrió un problema al intentar iniciar sesión. Inténtalo de nuevo.',
            <AlertCircle className="h-4 w-4" />,
          );
        }
      },
    });
  };

  return (
    <div className="relative container flex min-h-screen flex-col items-center justify-center md:grid lg:max-w-none lg:grid-cols-2 lg:px-0">
      {/* Imagen de fondo - columna derecha */}
      <div className="bg-muted relative order-1 hidden h-full flex-col lg:order-2 lg:flex">
        <div
          className="absolute inset-0 bg-cover bg-center bg-no-repeat"
          style={{ backgroundImage: 'url()' }}
        />
        <div className="absolute inset-0 bg-gradient-to-t from-black/70 via-black/50 to-black/30" />
        <div className="relative z-20 flex h-full flex-col justify-end p-8">
          {blockquoteText && blockquoteFooter ? (
            <blockquote className="space-y-2">
              <p className="text-xl font-medium text-white drop-shadow-md">
                &quot;{blockquoteText}&quot;
              </p>
              <footer className="text-sm text-white/80 drop-shadow-md">{blockquoteFooter}</footer>
            </blockquote>
          ) : (
            <div className="animate-pulse space-y-2">
              <div className="h-6 w-3/4 rounded bg-white/30"></div>
              <div className="h-6 w-full rounded bg-white/30"></div>
              <div className="h-6 w-2/3 rounded bg-white/30"></div>
              <div className="mt-2 h-4 w-1/3 rounded bg-white/20"></div>
            </div>
          )}
        </div>
      </div>

      {/* Formulario - columna izquierda */}
      <div className="order-2 w-full lg:order-1 lg:p-8">
        <div className="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[350px]">
          <div className="flex flex-col space-y-2 text-center">
            <h1 className="text-2xl font-semibold tracking-tight">{formTitle}</h1>
            {formDescription && <p className="text-muted-foreground text-sm">{formDescription}</p>}
          </div>

          <Head title={pageTitle ?? ''} />

          {status && (
            <div
              className="mb-4 rounded-md border border-green-200 bg-green-50 px-3 py-2 text-center text-sm text-green-700"
              role="status"
              aria-live="polite"
            >
              {status}
            </div>
          )}

          <div className="relative my-2 mb-4">
            <div className="absolute inset-0 flex items-center">
              <span className="border-muted-foreground/20 w-full border-t"></span>
            </div>
            <div className="relative flex justify-center text-xs uppercase">
              <span className="bg-background text-muted-foreground px-2">Ingresa tus datos</span>
            </div>
          </div>

          <form className="flex flex-col gap-4" onSubmit={submit}>
            <div className="grid gap-4">
              <div className="grid gap-2">
                <Label htmlFor="email">{emailFieldLabel}</Label>
                <Input
                  id="email"
                  type={emailFieldType}
                  required
                  autoComplete={emailFieldAutoComplete}
                  value={form.data.email}
                  onChange={(e) => {
                    form.setData('email', e.target.value);
                  }}
                  placeholder={emailFieldPlaceholder}
                  className={
                    form.errors.email
                      ? 'border-red-500 focus-visible:ring-red-500'
                      : 'focus:border-primary focus-visible:ring-primary/30'
                  }
                  disabled={isLocked || form.processing}
                  maxLength={100}
                />
              </div>

              <div className="grid gap-2">
                <div className="flex items-center">
                  <Label htmlFor="password">Contraseña</Label>
                  {canResetPassword && forgotPasswordUrl && (
                    <TextLink
                      href={forgotPasswordUrl}
                      className="hover:text-primary ml-auto text-sm"
                    >
                      ¿Olvidaste tu contraseña?
                    </TextLink>
                  )}
                </div>
                <Input
                  id="password"
                  type="password"
                  required
                  autoComplete="current-password"
                  value={form.data.password}
                  onChange={(e) => {
                    form.setData('password', e.target.value);
                  }}
                  placeholder="Contraseña"
                  className={
                    form.errors.password
                      ? 'border-red-500 focus-visible:ring-red-500'
                      : 'focus:border-primary focus-visible:ring-primary/30'
                  }
                  disabled={isLocked || form.processing}
                  maxLength={100}
                />
              </div>

              <div className="flex items-center space-x-3">
                <Checkbox
                  id="remember"
                  name="remember"
                  checked={form.data.remember}
                  onClick={() => {
                    form.setData('remember', !form.data.remember);
                  }}
                  disabled={isLocked || form.processing}
                />
                <Label htmlFor="remember" className="text-sm">
                  Recordarme
                </Label>
              </div>

              <Button
                type="submit"
                className="w-full font-medium transition-all hover:shadow-md"
                disabled={isLocked || form.processing}
                size="lg"
              >
                {form.processing && <LoaderCircle className="mr-2 h-4 w-4 animate-spin" />}
                {isLocked ? `Bloqueado (${lockCountdown}s)` : submitButtonText}
              </Button>

              {isLocked && (
                <p className="text-center text-sm text-red-500">
                  Demasiados intentos fallidos. Intenta de nuevo más tarde.
                </p>
              )}
            </div>
          </form>
        </div>
      </div>
    </div>
  );
}
