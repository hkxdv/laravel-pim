import { type LucideIcon } from 'lucide-react';

export interface IconProps {
  iconNode?: LucideIcon | null;
  className?: string;
}

export function Icon({ iconNode: IconComponent, className }: Readonly<IconProps>) {
  if (!IconComponent) {
    return null;
  }

  return <IconComponent className={className} />;
}
