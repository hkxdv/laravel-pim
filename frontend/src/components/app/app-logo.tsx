import { type ImgHTMLAttributes } from 'react';

// Componente interno para el icono del logo
function AppLogoIcon({ className, alt, ...props }: Readonly<ImgHTMLAttributes<HTMLImageElement>>) {
  return <img src="/laravel-logo.min.svg" className={className} alt={alt} {...props} />;
}

export default function AppLogo() {
  return (
    <>
      <div className="bg-muted flex aspect-square size-8 items-center justify-center rounded-md">
        <AppLogoIcon className="size-6" />
      </div>
      <div className="ml-1 grid flex-1 text-left text-sm">
        <span className="mb-0.5 truncate leading-none font-semibold"></span>
      </div>
    </>
  );
}

// Exportar también AppLogoIcon para compatibilidad con otros componentes
export { AppLogoIcon };
