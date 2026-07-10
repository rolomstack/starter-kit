import { Form, Head, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import DeleteBusinessModal from '@/components/delete-business-modal';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { InputGroup, InputGroupInput } from '@/components/ui/input-group';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { edit, update } from '@/routes/businesses';
import type {
    Business,
    BusinessPermissions,
    CountryData,
    PageProps,
} from '@/types';

type Props = {
    business: Business;
    permissions: BusinessPermissions;
    countries?: CountryData[];
};

export default function BusinessEdit({
    business,
    permissions,
    countries = [],
}: Props) {
    const { props } = usePage<PageProps>();
    const countriesList =
        countries.length > 0 ? countries : (props.countries ?? []);
    const [phoneCountry, setPhoneCountry] = useState<string>(
        business.phoneCountry || countriesList[0]?.code || 'US',
    );

    const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);

    const pageTitle = useMemo(
        () =>
            permissions.canUpdateBusiness
                ? `Edit ${business.name}`
                : `View ${business.name}`,
        [permissions.canUpdateBusiness, business.name],
    );

    return (
        <>
            <Head title={pageTitle} />

            <h1 className="sr-only">{pageTitle}</h1>

            <div className="flex flex-col space-y-10">
                <div className="space-y-6">
                    {permissions.canUpdateBusiness ? (
                        <>
                            <Heading
                                variant="small"
                                title="Business settings"
                                description="Update your business name and settings"
                            />

                            <Form
                                {...update.form(business.slug)}
                                className="space-y-6"
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="name">
                                                Business name
                                            </Label>
                                            <Input
                                                id="name"
                                                name="name"
                                                data-test="business-name-input"
                                                defaultValue={business.name}
                                                required
                                            />
                                            <InputError message={errors.name} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="email">
                                                Email Address
                                            </Label>
                                            <Input
                                                id="email"
                                                type="email"
                                                name="email"
                                                data-test="business-email-input"
                                                defaultValue={business.email}
                                                placeholder="Email Address"
                                            />
                                            <InputError
                                                message={errors.email}
                                            />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="phone">
                                                Phone Number (whatsapp
                                                preferred)
                                            </Label>
                                            <InputGroup>
                                                <Select
                                                    name="phone_country"
                                                    value={phoneCountry}
                                                    onValueChange={
                                                        setPhoneCountry
                                                    }
                                                >
                                                    <SelectTrigger className="h-10 w-[95px] rounded-r-none border-0 bg-transparent shadow-none focus-visible:border-0 focus-visible:ring-0">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        {countriesList.map(
                                                            (country) => (
                                                                <SelectItem
                                                                    key={
                                                                        country.code
                                                                    }
                                                                    value={
                                                                        country.code
                                                                    }
                                                                >
                                                                    <span>
                                                                        {
                                                                            country.flag
                                                                        }
                                                                    </span>
                                                                    <span>
                                                                        {
                                                                            country.dialCode
                                                                        }
                                                                    </span>
                                                                </SelectItem>
                                                            ),
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                <div className="h-6 w-px shrink-0 bg-border" />
                                                <InputGroupInput
                                                    id="phone"
                                                    type="text"
                                                    data-test="business-phone-input"
                                                    defaultValue={
                                                        business.phone
                                                    }
                                                    autoComplete="phone"
                                                    name="phone"
                                                    placeholder={
                                                        countriesList.find(
                                                            (c) =>
                                                                c.code ===
                                                                phoneCountry,
                                                        )?.placeholder ||
                                                        'Phone number'
                                                    }
                                                />
                                            </InputGroup>
                                            <InputError
                                                message={errors.phone}
                                            />
                                            <InputError
                                                message={errors.phone_country}
                                            />
                                        </div>

                                        <div className="flex items-center gap-4">
                                            <Button
                                                type="submit"
                                                data-test="business-save-button"
                                                disabled={processing}
                                            >
                                                Save
                                            </Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </>
                    ) : (
                        <>
                            <Heading variant="small" title={business.name} />
                        </>
                    )}
                </div>

                {permissions.canDeleteBusiness && !business.isPersonal ? (
                    <div className="space-y-6">
                        <Heading
                            variant="small"
                            title="Delete business"
                            description="Permanently delete your business"
                        />
                        <div className="space-y-4 rounded-lg border border-red-100 bg-red-50 p-4 dark:border-red-200/10 dark:bg-red-700/10">
                            <div className="relative space-y-0.5 text-red-600 dark:text-red-100">
                                <p className="font-medium">Warning</p>
                                <p className="text-sm">
                                    Please proceed with caution, this cannot be
                                    undone.
                                </p>
                            </div>
                            <Button
                                variant="destructive"
                                data-test="delete-business-button"
                                onClick={() => setDeleteDialogOpen(true)}
                            >
                                Delete business
                            </Button>
                        </div>
                    </div>
                ) : null}
            </div>

            {permissions.canDeleteBusiness && !business.isPersonal ? (
                <DeleteBusinessModal
                    business={business}
                    open={deleteDialogOpen}
                    onOpenChange={setDeleteDialogOpen}
                />
            ) : null}
        </>
    );
}

BusinessEdit.layout = (props: {
    business: { name: string; slug: string };
}) => ({
    breadcrumbs: [
        {
            title: props.business.name,
            href: edit(props.business.slug),
        },
    ],
});
