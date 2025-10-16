import { Icon } from '@/components/icon';
import {
  SidebarGroup,
  SidebarGroupLabel,
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
} from '@/components/ui/sidebar';
import { type NavItemDefinition } from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface NavMainProps {
  items: NavItemDefinition[];
  label?: string;
}

export function NavMain({ items = [], label }: Readonly<NavMainProps>) {
  const page = usePage();
  return (
    <SidebarGroup className="px-2 py-0">
      {label && <SidebarGroupLabel>{label}</SidebarGroupLabel>}
      <SidebarMenu>
        {items.map((item) => (
          <SidebarMenuItem key={item.title}>
            <SidebarMenuButton
              asChild
              isActive={item.href === page.url}
              tooltip={{ children: item.title }}
            >
              <Link href={item.href} prefetch>
                {item.icon && <Icon iconNode={item.icon} />}
                <span>{item.title}</span>
              </Link>
            </SidebarMenuButton>
          </SidebarMenuItem>
        ))}
      </SidebarMenu>
    </SidebarGroup>
  );
}
