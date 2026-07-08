import { useState } from 'react';
import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TeamInvitationAlert from '@/components/team-invitation-alert';
import TextLink from '@/components/text-link';
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
import { login } from '@/routes';
import { store } from '@/routes/register';
import type { TeamInvitationContext } from '@/types';

const COUNTRIES = [
    {
        code: 'US',
        name: 'United States',
        flag: '🇺🇸',
        dialCode: '+1',
        placeholder: '201 555 0123',
    },
    {
        code: 'CA',
        name: 'Canada',
        flag: '🇨🇦',
        dialCode: '+1',
        placeholder: '201 555 0123',
    },
    {
        code: 'GB',
        name: 'United Kingdom',
        flag: '🇬🇧',
        dialCode: '+44',
        placeholder: '7123 456789',
    },
    {
        code: 'AU',
        name: 'Australia',
        flag: '🇦🇺',
        dialCode: '+61',
        placeholder: '412 345 678',
    },
    {
        code: 'NZ',
        name: 'New Zealand',
        flag: '🇳🇿',
        dialCode: '+64',
        placeholder: '21 234 5678',
    },
    {
        code: 'NG',
        name: 'Nigeria',
        flag: '🇳🇬',
        dialCode: '+234',
        placeholder: '803 123 4567',
    },
] as const;

type Props = {
    passwordRules: string;
    teamInvitation?: TeamInvitationContext | null;
};

export default function Register({ passwordRules, teamInvitation }: Props) {
    const [phoneCountry, setPhoneCountry] = useState<string>('US');
    return (
        <>
            <Head title="Register" />
            <Form
                {...store.form()}
                resetOnSuccess={['password', 'password_confirmation']}
                disableWhileProcessing
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        {teamInvitation && (
                            <TeamInvitationAlert
                                invitation={teamInvitation}
                                action="Register"
                            />
                        )}

                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="name"
                                    name="name"
                                    placeholder="Full Name"
                                />
                                <InputError
                                    message={errors.name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="business_name">
                                    Business Name
                                </Label>
                                <Input
                                    id="business_name"
                                    type="text"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    name="business_name"
                                    placeholder="Business Name"
                                />
                                <InputError
                                    message={errors.business_name}
                                    className="mt-2"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    required
                                    tabIndex={2}
                                    autoComplete="email"
                                    name="email"
                                    placeholder="email@example.com"
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
                                            {COUNTRIES.map((country) => (
                                                <SelectItem
                                                    key={country.code}
                                                    value={country.code}
                                                >
                                                    <span>{country.flag}</span>
                                                    <span>
                                                        {country.dialCode}
                                                    </span>
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <div className="h-6 w-px shrink-0 bg-border" />
                                    <InputGroupInput
                                        id="phone"
                                        type="text"
                                        required
                                        tabIndex={2}
                                        autoComplete="phone"
                                        name="phone"
                                        placeholder={
                                            COUNTRIES.find(
                                                (c) => c.code === phoneCountry,
                                            )?.placeholder || 'Phone number'
                                        }
                                    />
                                </InputGroup>
                                <InputError message={errors.phone} />
                                <InputError message={errors.phone_country} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    required
                                    tabIndex={3}
                                    autoComplete="new-password"
                                    name="password"
                                    placeholder="Password"
                                    passwordrules={passwordRules}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    required
                                    tabIndex={4}
                                    autoComplete="new-password"
                                    name="password_confirmation"
                                    placeholder="Confirm password"
                                    passwordrules={passwordRules}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>

                            <Button
                                type="submit"
                                className="mt-2 w-full"
                                tabIndex={5}
                                data-test="register-user-button"
                            >
                                {processing && <Spinner />}
                                Create account
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Already have an account?{' '}
                            <TextLink
                                href={
                                    teamInvitation
                                        ? login.url({
                                              query: {
                                                  invitation:
                                                      teamInvitation.code,
                                              },
                                          })
                                        : login()
                                }
                                data-test="team-invitation-login-link"
                                tabIndex={6}
                            >
                                Log in
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>
        </>
    );
}

Register.layout = {
    title: 'Create an account',
    description: 'Enter your details below to create your account',
};
