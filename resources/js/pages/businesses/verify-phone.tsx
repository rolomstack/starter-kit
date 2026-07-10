import { useForm, Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { toast } from 'sonner';
import { BusinessSwitcher } from '@/components/business-switcher';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { InputGroup, InputGroupInput } from '@/components/ui/input-group';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
    InputOTPSeparator,
} from '@/components/ui/input-otp';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { logout } from '@/routes';
import { update } from '@/routes/businesses';
import { verify, resend } from '@/routes/businesses/phone';
import type { CountryData, PageProps } from '@/types';

interface BusinessInfo {
    name: string;
    slug: string;
    email: string;
    phone: string;
    phoneCountry?: string;
}

export default function BusinessVerifyPhone({
    business,
    status,
    countries = [],
    canUpdatePhone = false,
}: {
    business: BusinessInfo;
    status?: string;
    countries?: CountryData[];
    canUpdatePhone?: boolean;
}) {
    const page = usePage<PageProps>();
    const countriesList =
        countries.length > 0 ? countries : (page.props.countries ?? []);
    const businesses = page.props.businesses ?? [];
    const hasMultipleBusinesses = businesses.length > 1;

    const [isEditingPhone, setIsEditingPhone] = useState(false);
    const [phoneCountry, setPhoneCountry] = useState<string>(
        business.phoneCountry || countriesList[0]?.code || 'US',
    );

    const { data, setData, post, processing, errors } = useForm({
        code: '',
    });

    const updatePhoneForm = useForm({
        name: business.name,
        email: business.email || '',
        phone: business.phone || '',
        phone_country: business.phoneCountry || countriesList[0]?.code || 'US',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(verify.url({ business: business.slug }));
    };

    const resendCode = (e: React.FormEvent) => {
        e.preventDefault();
        post(resend.url({ business: business.slug }), {
            onSuccess: () => {
                toast.success(
                    'A new verification code has been sent to your email.',
                );
            },
        });
    };

    const handleLogout = (e: React.FormEvent) => {
        e.preventDefault();
        post(logout.url());
    };

    const submitPhoneUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        updatePhoneForm.patch(
            update.url({ business: business.slug }),
            {
                onSuccess: () => {
                    setIsEditingPhone(false);
                    toast.success('Phone number updated successfully.');
                },
            },
        );
    };

    const cancelEditing = () => {
        setIsEditingPhone(false);
        updatePhoneForm.setData({
            name: business.name,
            email: business.email || '',
            phone: business.phone || '',
            phone_country: business.phoneCountry || 'US',
        });
        setPhoneCountry(business.phoneCountry || 'US');
    };

    return (
        <>
            <Head title={`Verify Phone - ${business.name}`} />

            {/* Header section with active business and optional switcher */}
            <div className="flex items-center justify-between border-b pb-4 mb-6">
                <div className="text-left text-sm font-medium text-muted-foreground">Active Business:</div>
                {hasMultipleBusinesses && <BusinessSwitcher inline />}
            </div>

            {status === 'code-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    A new verification code has been sent to your email.
                </div>
            )}

            {isEditingPhone ? (
                <form onSubmit={submitPhoneUpdate} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="phone">
                            Phone Number (whatsapp preferred)
                        </Label>
                        <InputGroup>
                            <Select
                                name="phone_country"
                                value={phoneCountry}
                                onValueChange={(val) => {
                                    setPhoneCountry(val);
                                    updatePhoneForm.setData('phone_country', val);
                                }}
                                disabled={updatePhoneForm.processing}
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
                                value={updatePhoneForm.data.phone}
                                onChange={(e) =>
                                    updatePhoneForm.setData(
                                        'phone',
                                        e.target.value,
                                    )
                                }
                                disabled={updatePhoneForm.processing}
                                autoComplete="phone"
                                name="phone"
                                placeholder={
                                    countriesList.find(
                                        (c) => c.code === phoneCountry,
                                    )?.placeholder || 'Phone number'
                                }
                            />
                        </InputGroup>
                        <InputError message={updatePhoneForm.errors.phone} />
                        <InputError
                            message={updatePhoneForm.errors.phone_country}
                        />
                    </div>

                    <div className="flex gap-3">
                        <Button
                            type="submit"
                            className="flex-1"
                            disabled={updatePhoneForm.processing}
                        >
                            {updatePhoneForm.processing && <Spinner />}
                            Save Phone Number
                        </Button>
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={cancelEditing}
                            disabled={updatePhoneForm.processing}
                        >
                            Cancel
                        </Button>
                    </div>
                </form>
            ) : (
                <form onSubmit={submit} className="space-y-6">
                    <div className="flex flex-col items-center justify-center space-y-4">
                        <label className="text-center text-sm font-medium text-foreground">
                            Verification Code for {business.name}
                        </label>
                        <InputOTP
                            maxLength={6}
                            value={data.code}
                            onChange={(value) => setData('code', value)}
                            disabled={processing}
                        >
                            <InputOTPGroup>
                                <InputOTPSlot
                                    index={0}
                                    className="h-12 w-10 text-lg"
                                />
                                <InputOTPSlot
                                    index={1}
                                    className="h-12 w-10 text-lg"
                                />
                                <InputOTPSlot
                                    index={2}
                                    className="h-12 w-10 text-lg"
                                />
                            </InputOTPGroup>
                            <InputOTPSeparator />
                            <InputOTPGroup>
                                <InputOTPSlot
                                    index={3}
                                    className="h-12 w-10 text-lg"
                                />
                                <InputOTPSlot
                                    index={4}
                                    className="h-12 w-10 text-lg"
                                />
                                <InputOTPSlot
                                    index={5}
                                    className="h-12 w-10 text-lg"
                                />
                            </InputOTPGroup>
                        </InputOTP>
                        {errors.code && <InputError message={errors.code} />}

                        <div className="text-center text-sm text-muted-foreground mt-2">
                            {business.phone ? (
                                <>
                                    Code sent to:{' '}
                                    <span className="font-medium text-foreground">
                                        {business.phone}
                                    </span>
                                </>
                            ) : (
                                <span>No phone number set</span>
                            )}
                            {canUpdatePhone && (
                                <button
                                    type="button"
                                    onClick={() => setIsEditingPhone(true)}
                                    className="ml-2 text-sm text-primary font-medium hover:underline cursor-pointer border-0 bg-transparent"
                                    data-test="change-phone-button"
                                >
                                    Change
                                </button>
                            )}
                        </div>
                    </div>

                    <div className="space-y-4">
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={processing || data.code.length < 6}
                        >
                            {processing && <Spinner />}
                            Verify Business Phone
                        </Button>

                        <div className="flex flex-col items-center justify-center space-y-2">
                            <button
                                type="button"
                                onClick={resendCode}
                                disabled={processing}
                                className="cursor-pointer border-0 bg-transparent text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                            >
                                Resend verification code
                            </button>

                            <button
                                type="button"
                                onClick={handleLogout}
                                className="cursor-pointer border-0 bg-transparent text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
                            >
                                Log out
                            </button>
                        </div>
                    </div>
                </form>
            )}
        </>
    );
}

BusinessVerifyPhone.layout = {
    title: 'Verify business phone number',
    description:
        'Please enter the 6-digit verification code sent to your email to verify your business phone number.',
};
