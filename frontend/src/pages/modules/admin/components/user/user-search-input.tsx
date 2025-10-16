import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/utils/cn';
import { Search, X } from 'lucide-react';
import { useState } from 'react';

/**
 * Define las props para el componente `UsersSearchInput`.
 */
interface UsersSearchInputProps {
  /** El valor actual del término de búsqueda. */
  searchTermValue: string;
  /** Función de callback que se ejecuta cuando el valor del input cambia. */
  onSearchTermChange: (value: string) => void;
  /** Función de callback que se ejecuta cuando se hace clic en el botón de limpiar. */
  onClearSearchClick: () => void;
  /** Clase CSS adicional para el contenedor. */
  className?: string;
}

/**
 * Un componente de campo de entrada controlado, diseñado para la búsqueda de usuarios.
 *
 * Incluye un icono de búsqueda, un placeholder informativo y un botón para limpiar
 * el término de búsqueda actual, que solo aparece cuando hay texto en el campo.
 *
 * @param props - Las props para el componente de búsqueda.
 * @returns Un campo de entrada de búsqueda.
 */
export default function UsersSearchInput({
  searchTermValue,
  onSearchTermChange,
  onClearSearchClick,
  className,
}: Readonly<UsersSearchInputProps>) {
  const [isFocused, setIsFocused] = useState(false);

  return (
    <div className={cn('relative w-full max-w-md transition-all duration-200', className)}>
      <Label htmlFor="user-search-input" className="sr-only">
        Buscar usuarios
      </Label>
      <div
        className={cn(
          'border-border relative flex items-center rounded-md border transition-all duration-200',
          isFocused ? 'ring-primary/20 ring-offset-background ring-2 ring-offset-1' : '',
          searchTermValue ? 'bg-background' : 'bg-muted/40',
        )}
      >
        <Search
          className={cn(
            'absolute left-3 h-4 w-4 transition-colors duration-200',
            isFocused || searchTermValue ? 'text-primary' : 'text-muted-foreground',
          )}
        />
        <Input
          id="user-search-input"
          name="user_search"
          type="text"
          placeholder="Buscar por nombre, email o rol..."
          className="h-10 border-0 bg-transparent pr-10 pl-10 focus-visible:ring-0 focus-visible:ring-offset-0"
          value={searchTermValue}
          onChange={(e) => {
            onSearchTermChange(e.target.value);
          }}
          onFocus={() => {
            setIsFocused(true);
          }}
          onBlur={() => {
            setIsFocused(false);
          }}
        />
        {searchTermValue && (
          <Button
            type="button"
            onClick={onClearSearchClick}
            variant="ghost"
            size="icon"
            className="hover:bg-muted absolute right-1 h-8 w-8 rounded-full p-0 opacity-70 hover:opacity-100"
            aria-label="Limpiar búsqueda"
          >
            <X className="h-4 w-4" />
          </Button>
        )}
      </div>
    </div>
  );
}
