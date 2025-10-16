import { useTheme, type Theme } from '@/providers/theme-provider';
import { cn } from '@/utils/cn';
import { Monitor, Moon, Sun, type LucideIcon } from 'lucide-react';
import { useMemo, type HTMLAttributes, type KeyboardEvent } from 'react';

export default function AppearanceToggleTab({
  className = '',
  ...props
}: Readonly<HTMLAttributes<HTMLDivElement>>) {
  const { theme, setTheme } = useTheme();

  const tabs = useMemo<{ value: Theme; icon: LucideIcon; label: string }[]>(
    () => [
      { value: 'light', icon: Sun, label: 'Claro' },
      { value: 'dark', icon: Moon, label: 'Oscuro' },
      { value: 'system', icon: Monitor, label: 'Sistema' },
    ],
    [],
  );

  const currentIndex = useMemo(() => tabs.findIndex((t) => t.value === theme), [tabs, theme]);

  function onKeyDown(e: KeyboardEvent<HTMLButtonElement>) {
    if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
    e.preventDefault();
    const dir = e.key === 'ArrowRight' ? 1 : -1;
    const nextIndex = (currentIndex + dir + tabs.length) % tabs.length;
    const nextTab = tabs[nextIndex];
    if (nextTab) setTheme(nextTab.value);
  }

  return (
    <div
      className={cn('relative w-full', className)}
      role="tablist"
      aria-label="Selector de tema"
      {...props}
    >
      {/* Tabs */}
      <div className="flex items-end gap-12">
        {tabs.map(({ value, icon: Icon, label }) => {
          const isActive = theme === value;
          const tabId = `appearance-tab-${value}`;
          return (
            <button
              key={value}
              type="button"
              id={tabId}
              role="tab"
              aria-selected={isActive}
              tabIndex={isActive ? 0 : -1}
              onKeyDown={onKeyDown}
              onClick={() => {
                setTheme(value);
              }}
              className={cn(
                'group relative flex min-w-[5rem] flex-col items-center px-2 pt-3 pb-2 transition-colors outline-none',
                isActive
                  ? 'text-neutral-900 dark:text-neutral-100'
                  : 'text-neutral-500 hover:text-neutral-800 dark:text-neutral-400 dark:hover:text-neutral-200',
              )}
            >
              <Icon
                className={cn(
                  'mb-2 h-7 w-7 transition-colors',
                  isActive
                    ? 'text-neutral-900 dark:text-neutral-100'
                    : 'text-neutral-400 group-hover:text-neutral-700 dark:text-neutral-500 dark:group-hover:text-neutral-200',
                )}
              />
              <span
                className={cn(
                  'pt-0.5 text-sm leading-none',
                  isActive ? 'font-medium' : 'font-normal',
                )}
              >
                {label}
              </span>

              {/* Active underline under the tab */}
              <span
                aria-hidden="true"
                className={cn(
                  'pointer-events-none absolute inset-x-0 -bottom-[1px] h-[3px] rounded bg-neutral-900 transition-opacity dark:bg-neutral-100',
                  isActive ? 'opacity-100' : 'opacity-0',
                )}
              />

              {/* Focus ring for accessibility */}
              <span
                aria-hidden
                className="pointer-events-none absolute inset-0 rounded-md ring-0 ring-blue-500 group-focus-visible:ring-2 focus:!ring-2 focus:!ring-offset-2 focus:!ring-offset-white dark:focus:!ring-offset-neutral-900"
              />
            </button>
          );
        })}
      </div>

      {/* Baseline under all tabs */}
      <div className="pointer-events-none absolute right-0 bottom-0 left-0 h-px bg-neutral-200 dark:bg-neutral-700" />
    </div>
  );
}
