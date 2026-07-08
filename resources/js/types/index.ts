import type { Auth } from './auth';
import type { Business } from './businesses';

export type * from './auth';
export type * from './navigation';
export type * from './businesses';
export type * from './ui';

export type CountryData = {
    code: string;
    name: string;
    flag: string;
    dialCode: string;
    placeholder: string;
};

export type PageProps<
    T extends Record<string, unknown> = Record<string, unknown>,
> = T & {
    name: string;
    auth: Auth;
    sidebarOpen: boolean;
    currentBusiness: Business | null;
    businesses: Business[];
    countries?: CountryData[];
};

