import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { motion } from 'motion/react';

interface HoverableNavLinkProps {
  href: string;
  Icon: LucideIcon;
  text: string;
  isActive: boolean;
  onMouseEnter: () => void;
}

export function HoverableNavLink({
  href,
  Icon,
  text,
  isActive,
  onMouseEnter,
}: Readonly<HoverableNavLinkProps>) {
  return (
    <Link
      href={href}
      className="text-foreground relative z-10 flex items-center rounded-lg px-3 py-2 text-sm font-medium transition-colors duration-300"
      onMouseEnter={onMouseEnter}
    >
      {isActive && (
        <motion.div
          layoutId="hover-background"
          className="bg-muted absolute inset-0 rounded-lg"
          initial={{ opacity: 0 }}
          animate={{ opacity: 1, transition: { duration: 0.2 } }}
          exit={{ opacity: 0, transition: { duration: 0.2 } }}
        />
      )}
      <Icon className="relative z-10 mr-2 h-4 w-4" />
      <span className="relative z-10">{text}</span>
    </Link>
  );
}
