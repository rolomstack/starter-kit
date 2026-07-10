import { router, usePage } from '@inertiajs/react';
import { Check, ChevronsUpDown, Plus, Users } from 'lucide-react';
import CreateBusinessModal from '@/components/create-business-modal';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useIsMobile } from '@/hooks/use-mobile';
import { switchMethod } from '@/routes/businesses';
import type { PageProps } from '@/types';
import type { Business } from '@/types';

type BusinessSwitcherProps = {
    inline?: boolean;
};

export function BusinessSwitcher({ inline = false }: BusinessSwitcherProps) {
    const page = usePage<PageProps>();
    const isMobile = useIsMobile();
    const currentBusiness = page.props.currentBusiness;
    const businesses = page.props.businesses ?? [];

    const switchBusiness = (business: Business) => {
        const previousBusinessSlug = currentBusiness?.slug;

        router.visit(switchMethod(business.slug), {
            onFinish: () => {
                if (!previousBusinessSlug || typeof window === 'undefined') {
                    router.reload();

                    return;
                }

                const currentUrl = `${window.location.pathname}${window.location.search}${window.location.hash}`;
                const segment = `/${previousBusinessSlug}`;

                if (currentUrl.includes(segment)) {
                    router.visit(
                        currentUrl.replace(segment, `/${business.slug}`),
                        {
                            replace: true,
                        },
                    );

                    return;
                }

                router.reload();
            },
        });
    };

    return (
        <DropdownMenu>
            <DropdownMenuTrigger asChild>
                <Button
                    variant="ghost"
                    data-test="business-switcher-trigger"
                    className={
                        inline
                            ? 'h-11 gap-1 px-2'
                            : 'w-full justify-start px-2 has-[>svg]:px-2 data-[state=open]:bg-sidebar-accent data-[state=open]:text-sidebar-accent-foreground'
                    }
                >
                    <Users
                        className={
                            inline
                                ? 'hidden'
                                : 'hidden size-4 shrink-0 group-data-[collapsible=icon]:block'
                        }
                    />
                    <div
                        className={
                            inline
                                ? 'grid flex-1 text-left text-sm leading-tight'
                                : 'grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden'
                        }
                    >
                        <span
                            className={
                                inline
                                    ? 'w-full max-w-30 truncate font-medium'
                                    : 'truncate font-semibold'
                            }
                        >
                            {currentBusiness?.name ?? 'Select business'}
                        </span>
                    </div>
                    <ChevronsUpDown
                        className={
                            inline
                                ? 'size-4 opacity-50'
                                : 'ml-auto group-data-[collapsible=icon]:hidden'
                        }
                    />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                className={
                    inline
                        ? 'w-56'
                        : 'w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-lg'
                }
                side={inline ? undefined : isMobile ? 'bottom' : 'right'}
                align={inline ? 'end' : 'start'}
                sideOffset={inline ? undefined : 4}
            >
                <DropdownMenuLabel className="text-xs text-muted-foreground">
                    Businesses
                </DropdownMenuLabel>
                {businesses.map((business) => (
                    <DropdownMenuItem
                        key={business.id}
                        data-test="business-switcher-item"
                        className={
                            inline
                                ? 'cursor-pointer gap-2'
                                : 'cursor-pointer gap-2 p-2'
                        }
                        onSelect={() => switchBusiness(business)}
                    >
                        {business.name}
                        {currentBusiness?.id === business.id && (
                            <Check
                                className={
                                    inline
                                        ? 'ml-auto size-4'
                                        : 'ml-auto h-4 w-4'
                                }
                            />
                        )}
                    </DropdownMenuItem>
                ))}
                <DropdownMenuSeparator />
                <CreateBusinessModal>
                    <DropdownMenuItem
                        data-test="business-switcher-new-business"
                        className={
                            inline
                                ? 'cursor-pointer gap-2'
                                : 'cursor-pointer gap-2 p-2'
                        }
                        onSelect={(event) => event.preventDefault()}
                    >
                        <Plus className={inline ? 'size-4' : 'h-4 w-4'} />
                        <span className="text-muted-foreground">
                            New business
                        </span>
                    </DropdownMenuItem>
                </CreateBusinessModal>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
