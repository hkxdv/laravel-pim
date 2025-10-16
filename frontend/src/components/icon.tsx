import { icons, type LucideIcon, type LucideProps } from 'lucide-react';

interface IconProps extends Omit<LucideProps, 'ref'> {
  iconNode?: LucideIcon | string;
  name?: keyof typeof icons;
}

export function Icon({ iconNode, name, className, ...props }: Readonly<IconProps>) {
  if (name) {
    const LucideIconComponent = icons[name];
    return <LucideIconComponent className={className} {...props} />;
  }

  if (typeof iconNode === 'string') {
    const LucideIconComponent = icons[iconNode as keyof typeof icons];
    return <LucideIconComponent className={className} {...props} />;
  }

  if (iconNode) {
    const LucideIconFinal = iconNode;
    return <LucideIconFinal className={className} {...props} />;
  }

  return null;
}
