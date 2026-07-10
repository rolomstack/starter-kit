import { Link, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useCurrentUrl } from '@/hooks/use-current-url';
import { cn, toUrl } from '@/lib/utils';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editBusiness } from '@/routes/businesses';
import { index as subscriptionsIndex } from '@/routes/businesses/subscriptions';
import { index as teamsIndex } from '@/routes/businesses/teams';
import { edit } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import type { NavItem, PageProps } from '@/types';
export default function SettingsLayout({ children }: PropsWithChildren) {
    const { isCurrentOrParentUrl, isCurrentUrl } = useCurrentUrl();
    const { props } = usePage<PageProps>();
    const currentBusinessSlug = props.currentBusiness?.slug ?? '';

    const sidebarNavItems: NavItem[] = [
        {
            title: 'Profile',
            href: edit(),
            icon: null,
        },
        {
            title: 'Security',
            href: editSecurity(),
            icon: null,
        },
        {
            title: 'Business',
            href: editBusiness({ business: currentBusinessSlug }),
            icon: null,
            exactMatch: true,
        },
        {
            title: 'Teams',
            href: teamsIndex({ business: currentBusinessSlug }),
            icon: null,
        },
        {
            title: 'Subscription',
            href: subscriptionsIndex({ business: currentBusinessSlug }),
            icon: null,
        },
        {
            title: 'Appearance',
            href: editAppearance(),
            icon: null,
        },
    ];

    return (
        <div className="px-4 py-6">
            <Heading
                title="Settings"
                description="Manage your profile and account settings"
            />

            <div className="flex flex-col lg:flex-row lg:space-x-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav
                        className="flex flex-col space-y-1 space-x-0"
                        aria-label="Settings"
                    >
                        {sidebarNavItems.map((item, index) => (
                            <Button
                                key={`${toUrl(item.href)}-${index}`}
                                size="sm"
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start', {
                                    'bg-muted': item.exactMatch
                                        ? isCurrentUrl(item.href)
                                        : isCurrentOrParentUrl(item.href),
                                })}
                            >
                                <Link href={item.href}>
                                    {item.icon && (
                                        <item.icon className="h-4 w-4" />
                                    )}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">
                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
