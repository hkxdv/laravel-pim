import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import DeleteUser from '@/pages/settings/components/auto-delete';
import { BasicInfoCard } from '@/pages/settings/components/profile/basic-info-card';
import type { ProfilePageProps as ProfilePagePropsLocal } from '@/pages/settings/components/profile/types';
import type { BreadcrumbItem } from '@/types';
import { extractUserData } from '@/utils/user-data';
import type { PageProps } from '@inertiajs/core';
import { Head, usePage } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
  {
    title: 'Configuración de perfil',
    href: '/settings/profile',
  },
];

export default function ProfilePage({
  mustVerifyEmail = false,
  status,
}: Readonly<PageProps & { mustVerifyEmail?: boolean; status?: string }>) {
  const { auth, contextualNavItems } = usePage<ProfilePagePropsLocal>().props;

  // Extraer datos del usuario usando la función auxiliar
  const userData = extractUserData(auth.user);
  const isStaffUser = !!userData;

  const initialName = userData?.name ?? '';
  const initialEmail = userData?.email ?? '';

  return (
    <AppLayout
      user={userData}
      breadcrumbs={breadcrumbs}
      contextualNavItems={contextualNavItems ?? []}
    >
      <Head title="Configuración de perfil" />

      <SettingsLayout>
        <div className="space-y-8">
          <HeadingSmall
            title="Configuración de perfil"
            description="Gestiona tu información personal"
          />

          {/* Única sección: Información básica del usuario */}

          <BasicInfoCard
            initialName={initialName}
            initialEmail={initialEmail}
            mustVerifyEmail={mustVerifyEmail}
            isStaffUser={isStaffUser}
            emailVerifiedAt={userData?.email_verified_at ?? null}
            status={status ?? ''}
          />
        </div>

        <DeleteUser />
      </SettingsLayout>
    </AppLayout>
  );
}
