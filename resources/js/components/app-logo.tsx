import { ShoppingBag } from 'lucide-react';

export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center rounded bg-sidebar-primary text-sidebar-primary-foreground">
                <ShoppingBag className="size-5 fill-current stroke-primary text-white" />
            </div>
            <div className="ml-1 grid flex-1 text-left text-lg">
                <span className="mb-0.5 truncate leading-tight font-semibold">
                    Amarkit
                </span>
            </div>
        </>
    );
}
