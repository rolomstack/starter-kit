import { Form, usePage } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
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
import { store } from '@/routes/businesses';
import type { PageProps } from '@/types';

export default function CreateBusinessModal({ children }: PropsWithChildren) {
    const [open, setOpen] = useState(false);
    const { props } = usePage<PageProps>();
    const userEmail = props.auth.user?.email || '';
    const countriesList = props.countries ?? [];
    const [phoneCountry, setPhoneCountry] = useState<string>(
        countriesList[0]?.code || 'US',
    );

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>{children}</DialogTrigger>
            <DialogContent>
                <Form
                    key={String(open)}
                    {...store.form()}
                    className="space-y-6"
                    onSuccess={() => setOpen(false)}
                >
                    {({ errors, processing }) => (
                        <>
                            <DialogHeader>
                                <DialogTitle>Create a new business</DialogTitle>
                                <DialogDescription>
                                    Create a new business to collaborate with
                                    others.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="name">Business name</Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        data-test="create-business-name"
                                        placeholder="My business"
                                        required
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email address</Label>
                                    <Input
                                        id="email"
                                        name="email"
                                        type="email"
                                        defaultValue={userEmail}
                                        placeholder="colleague@example.com"
                                    />
                                    <InputError message={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="phone">
                                        Phone number (optional)
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
                            </div>

                            <DialogFooter className="gap-2">
                                <DialogClose asChild>
                                    <Button variant="secondary">Cancel</Button>
                                </DialogClose>

                                <Button
                                    type="submit"
                                    data-test="create-business-submit"
                                    disabled={processing}
                                >
                                    Create business
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}

