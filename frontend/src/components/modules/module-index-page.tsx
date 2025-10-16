import AppLayout from '@/layouts/app-layout';
import { ModuleDashboardLayout } from '@/layouts/module-dashboard-layout';
import type { BreadcrumbItem, NavItemDefinition, StaffUser } from '@/types';
import { Head } from '@inertiajs/react';
import type { ReactNode } from 'react';

export interface ModuleIndexPageProps {
  user: StaffUser | null;
  breadcrumbs: BreadcrumbItem[];
  contextualNavItems?: NavItemDefinition[];
  pageTitle: string;
  description?: string;
  staffUserName: string;
  stats: ReactNode;
  mainContent: ReactNode;
  fullWidth?: boolean;
}

export function ModuleIndexPage({
  user,
  breadcrumbs,
  contextualNavItems = [],
  pageTitle,
  description = '',
  staffUserName,
  stats,
  mainContent,
  fullWidth = true,
}: Readonly<ModuleIndexPageProps>) {
  return (
    <AppLayout
      user={user}
      breadcrumbs={breadcrumbs}
      contextualNavItems={contextualNavItems}
      pageTitle={pageTitle}
      pageDescription={description}
    >
      <Head title={pageTitle} />
      <ModuleDashboardLayout
        title={pageTitle}
        description={description}
        userName={staffUserName}
        stats={stats}
        mainContent={mainContent}
        fullWidth={fullWidth}
      />
    </AppLayout>
  );
}
