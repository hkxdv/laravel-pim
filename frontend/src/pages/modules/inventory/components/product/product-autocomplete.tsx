import { Input } from '@/components/ui/input';
import { ScrollArea } from '@/components/ui/scroll-area';
import { cn } from '@/utils/cn';
import React, { useEffect, useMemo, useRef, useState } from 'react';
import { route } from 'ziggy-js';

interface SuggestionItem {
  id: string | number | null;
  name: string;
  sku?: string | null;
  brand?: string | null;
  model?: string | null;
  price?: string | number | null;
  stock?: number | null;
  image_url?: string | null;
  highlight?: {
    name?: string | null;
    sku?: string | null;
    brand?: string | null;
    model?: string | null;
  };
}

export interface ProductAutocompleteProps {
  value: string;
  onChange: (next: string) => void;
  onSelect?: (item: SuggestionItem) => void;
  placeholder?: string;
  className?: string;
}

export const ProductAutocomplete: React.FC<ProductAutocompleteProps> = ({
  value,
  onChange,
  onSelect,
  placeholder = 'Buscar productos...',
  className,
}) => {
  const buildSuggestionKey = (item: SuggestionItem, index: number) => {
    const idPart = item.id == null ? undefined : String(item.id);
    const skuPart = typeof item.sku === 'string' && item.sku ? item.sku : undefined;
    const base = idPart ?? skuPart ?? `name-${item.name}`;
    return `${base}-${index}`;
  };

  const [open, setOpen] = useState(false);
  const [loading, setLoading] = useState(false);
  const [items, setItems] = useState<SuggestionItem[]>([]);
  const abortRef = useRef<AbortController | null>(null);
  const containerRef = useRef<HTMLDivElement | null>(null);

  const url = useMemo(
    () =>
      route('internal.inventory.products.suggest', {
        q: value,
        per_page: 5,
      }),
    [value],
  );

  useEffect(() => {
    if (!value || value.trim() === '') {
      setItems([]);
      setOpen(false);
      return;
    }
    const run = async () => {
      setLoading(true);
      abortRef.current?.abort();
      const ctrl = new AbortController();
      abortRef.current = ctrl;
      try {
        const res = await fetch(url, { signal: ctrl.signal });
        const json = (await res.json()) as { items?: SuggestionItem[] };
        const nextItems = Array.isArray(json.items) ? json.items : [];
        setItems(nextItems);
        setOpen(nextItems.length > 0);
      } catch {
        // ignore aborts
      } finally {
        setLoading(false);
      }
    };
    const timer = setTimeout(() => {
      void run();
    }, 200);
    return () => {
      clearTimeout(timer);
      abortRef.current?.abort();
    };
  }, [url, value]);

  useEffect(() => {
    const handler = (ev: MouseEvent) => {
      if (!containerRef.current) return;
      if (!containerRef.current.contains(ev.target as Node)) {
        setOpen(false);
      }
    };
    globalThis.addEventListener('click', handler);
    return () => {
      globalThis.removeEventListener('click', handler);
    };
  }, []);

  return (
    <div ref={containerRef} className={cn('relative w-[220px] sm:w-[280px]', className)}>
      <Input
        type="search"
        aria-label="Buscar productos"
        placeholder={placeholder}
        value={value}
        onChange={(e) => {
          onChange(e.target.value);
        }}
        onFocus={() => {
          setOpen(items.length > 0);
        }}
      />
      {open && (
        <div className="bg-popover text-popover-foreground absolute z-30 mt-1 w-full rounded-md border shadow-sm">
          <ScrollArea className="max-h-64">
            <ul>
              {items.map((it, i) => (
                <li
                  key={buildSuggestionKey(it, i)}
                  role="option"
                  aria-selected={false}
                  className="hover:bg-accent/50 cursor-pointer px-2 py-2 text-sm"
                  onMouseDown={(e) => {
                    e.preventDefault();
                    onSelect?.(it);
                    onChange(it.name);
                    setOpen(false);
                  }}
                >
                  <div className="flex items-center gap-2">
                    {it.image_url ? (
                      <img src={it.image_url} alt="" className="size-8 rounded object-cover" />
                    ) : (
                      <div className="bg-muted size-8 rounded" />
                    )}
                    <div className="flex-1">
                      <div className="leading-tight font-medium">
                        {it.highlight?.name ? (
                          <span
                            // Typesense returns safe highlight snippets; render intentionally
                            dangerouslySetInnerHTML={{ __html: it.highlight.name }}
                          />
                        ) : (
                          it.name
                        )}
                      </div>
                      <div className="text-muted-foreground text-xs">
                        <span>
                          SKU{' '}
                          {it.highlight?.sku ? (
                            <span dangerouslySetInnerHTML={{ __html: it.highlight.sku }} />
                          ) : (
                            it.sku
                          )}
                        </span>{' '}
                        •{' '}
                        <span>
                          {it.highlight?.brand ? (
                            <span dangerouslySetInnerHTML={{ __html: it.highlight.brand }} />
                          ) : (
                            (it.brand ?? '')
                          )}
                        </span>{' '}
                        <span>
                          {it.highlight?.model ? (
                            <span dangerouslySetInnerHTML={{ __html: it.highlight.model }} />
                          ) : (
                            (it.model ?? '')
                          )}
                        </span>
                      </div>
                    </div>
                    {typeof it.price === 'string' || typeof it.price === 'number' ? (
                      <div className="text-xs">${String(it.price)}</div>
                    ) : null}
                  </div>
                </li>
              ))}
              {loading && (
                <li className="text-muted-foreground px-2 py-2 text-xs">Cargando sugerencias…</li>
              )}
              {!loading && items.length === 0 && (
                <li className="text-muted-foreground px-2 py-2 text-xs">Sin sugerencias</li>
              )}
            </ul>
          </ScrollArea>
        </div>
      )}
    </div>
  );
};
