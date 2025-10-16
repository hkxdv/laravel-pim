import type { NavItemDefinition } from '@/types';
import type { PageProps } from '@inertiajs/core';

export interface BasicInfoForm {
  name: string;
  email: string;
}

export interface ProfilePageProps extends PageProps {
  mustVerifyEmail?: boolean;
  status?: string;
  contextualNavItems?: NavItemDefinition[];
}

export interface BasicInfoCardProps {
  initialName: string;
  initialEmail: string;
  mustVerifyEmail: boolean;
  isStaffUser: boolean;
  emailVerifiedAt: string | null;
  status?: string;
}
