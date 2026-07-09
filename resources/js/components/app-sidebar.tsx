import { Link, usePage } from '@inertiajs/react';
import { LayoutGrid, Settings } from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { BusinessSwitcher } from '@/components/business-switcher';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { PageProps } from '@/types';
import type { NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage<PageProps>();
    const dashboardUrl = page.props.currentBusiness
        ? dashboard(page.props.currentBusiness.slug)
        : '/';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
        {
            title: 'Settings',
            href: editProfile(),
            icon: Settings,
            items: [
                {
                    title: 'Profile',
                    href: editProfile(),
                },
                {
                    title: 'Security',
                    href: editSecurity(),
                },
                {
                    title: 'Appearance',
                    href: editAppearance(),
                },
            ],
        },
    ];


    const footerNavItems: NavItem[] = [];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <BusinessSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
