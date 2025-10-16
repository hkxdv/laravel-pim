import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { type ReactNode } from 'react';

interface AppSidebarHeaderProps {
  breadcrumbs?: BreadcrumbItemType[];
  headerActions?: ReactNode;
}

export function AppSidebarHeader({
  breadcrumbs = [],
  headerActions,
}: Readonly<AppSidebarHeaderProps>) {
  return (
    <header className="border-sidebar-border/50 flex h-auto min-h-16 shrink-0 items-start justify-between gap-4 border-b p-4 sm:items-center sm:px-6 sm:py-3">
      <div className="flex flex-1 items-center gap-2">
        <SidebarTrigger className="-ml-1 sm:self-center" />
        <div className="flex flex-col">
          <Breadcrumbs breadcrumbs={breadcrumbs} />
        </div>
      </div>
      {headerActions && <div className="flex items-center gap-2">{headerActions}</div>}
    </header>
  );
}
