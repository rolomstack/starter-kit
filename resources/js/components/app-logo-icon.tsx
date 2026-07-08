import { ShoppingBag } from 'lucide-react';
import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <div className="flex aspect-square size-12 items-center justify-center rounded-md bg-sidebar-primary text-sidebar-primary-foreground">
            <ShoppingBag
                {...props}
                className="fill-current stroke-primary text-white"
            />
        </div>
    );
}
