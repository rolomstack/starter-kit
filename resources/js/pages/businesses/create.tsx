import { Form, Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
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
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/businesses';
import type { CountryData, PageProps } from '@/types';

type Props = {
    countries?: CountryData[];
};

export default function BusinessesCreate({ countries = [] }: Props) {
    const { props } = usePage<PageProps>();
    const userEmail = props.auth.user?.email || '';
    const countriesList = countries.length > 0 ? countries : (props.countries ?? []);
    const [phoneCountry, setPhoneCountry] = useState<string>(
        countriesList[0]?.code || 'US',
    );

    return (
        <>
            <Head title="Setup Business" />
            <Form
                {...store.form()}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <div className="grid gap-6">
                        <div className="grid gap-2">
                            <Label htmlFor="name">Business Name</Label>
                            <Input
                                id="name"
                                type="text"
                                required
                                autoFocus
                                tabIndex={1}
                                name="name"
                                placeholder="Business Name"
                            />
                            <InputError message={errors.name} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="email">Email Address</Label>
                            <Input
                                id="email"
                                type="email"
                                name="email"
                                defaultValue={userEmail}
                                placeholder="Email Address"
                                tabIndex={2}
                            />
                            <InputError message={errors.email} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="phone">
                                Phone Number (whatsapp preferred)
                            </Label>
                            <InputGroup>
                                <Select
                                    name="phone_country"
                                    value={phoneCountry}
                                    onValueChange={setPhoneCountry}
                                >
                                    <SelectTrigger className="h-10 w-[95px] rounded-r-none border-0 bg-transparent shadow-none focus-visible:border-0 focus-visible:ring-0">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {countriesList.map((country) => (
                                            <SelectItem
                                                key={country.code}
                                                value={country.code}
                                            >
                                                <span>{country.flag}</span>
                                                <span>{country.dialCode}</span>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <div className="h-6 w-px shrink-0 bg-border" />
                                <InputGroupInput
                                    id="phone"
                                    type="text"
                                    required
                                    tabIndex={3}
                                    autoComplete="phone"
                                    name="phone"
                                    placeholder={
                                        countriesList.find(
                                            (c) => c.code === phoneCountry,
                                        )?.placeholder || 'Phone number'
                                    }
                                />
                            </InputGroup>
                            <InputError message={errors.phone} />
                            <InputError message={errors.phone_country} />
                        </div>

                        <Button
                            type="submit"
                            className="mt-2 w-full"
                            tabIndex={4}
                            disabled={processing}
                            data-test="setup-business-button"
                        >
                            {processing && <Spinner />}
                            Setup Business
                        </Button>
                    </div>
                )}
            </Form>
        </>
    );
}

BusinessesCreate.layout = {
    title: 'Setup your business',
    description: 'Enter your business details below to get started',
};
