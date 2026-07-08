import { useForm, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { InputOTP, InputOTPGroup, InputOTPSlot, InputOTPSeparator } from '@/components/ui/input-otp';
import { logout } from '@/routes';
import { verify, resend } from '@/routes/businesses/phone';
import { toast } from 'sonner';

interface BusinessInfo {
    name: string;
    slug: string;
    phone: string;
}

export default function BusinessVerifyPhone({ business, status }: { business: BusinessInfo; status?: string }) {
    const { data, setData, post, processing, errors } = useForm({
        code: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(verify.url({ current_business: business.slug }));
    };

    const resendCode = (e: React.FormEvent) => {
        e.preventDefault();
        post(resend.url({ current_business: business.slug }), {
            onSuccess: () => {
                toast.success('A new verification code has been sent to your email.');
            },
        });
    };

    const handleLogout = (e: React.FormEvent) => {
        e.preventDefault();
        post(logout.url());
    };

    return (
        <>
            <Head title={`Verify Phone - ${business.name}`} />

            {status === 'code-sent' && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    A new verification code has been sent to your email.
                </div>
            )}

            <form onSubmit={submit} className="space-y-6">
                <div className="flex flex-col items-center justify-center space-y-4">
                    <label className="text-sm font-medium text-foreground text-center">
                        Verification Code for {business.name}
                    </label>
                    <InputOTP
                        maxLength={6}
                        value={data.code}
                        onChange={(value) => setData('code', value)}
                        disabled={processing}
                    >
                        <InputOTPGroup>
                            <InputOTPSlot index={0} className="w-10 h-12 text-lg" />
                            <InputOTPSlot index={1} className="w-10 h-12 text-lg" />
                            <InputOTPSlot index={2} className="w-10 h-12 text-lg" />
                        </InputOTPGroup>
                        <InputOTPSeparator />
                        <InputOTPGroup>
                            <InputOTPSlot index={3} className="w-10 h-12 text-lg" />
                            <InputOTPSlot index={4} className="w-10 h-12 text-lg" />
                            <InputOTPSlot index={5} className="w-10 h-12 text-lg" />
                        </InputOTPGroup>
                    </InputOTP>
                    {errors.code && (
                        <div className="text-sm text-destructive font-medium mt-1">
                            {errors.code}
                        </div>
                    )}
                </div>

                <div className="space-y-4">
                    <Button type="submit" className="w-full" disabled={processing || data.code.length < 6}>
                        {processing && <Spinner />}
                        Verify Business Phone
                    </Button>

                    <div className="flex flex-col items-center justify-center space-y-2">
                        <button
                            type="button"
                            onClick={resendCode}
                            disabled={processing}
                            className="text-sm text-muted-foreground hover:text-foreground underline underline-offset-4 bg-transparent border-0 cursor-pointer"
                        >
                            Resend verification code
                        </button>

                        <button
                            type="button"
                            onClick={handleLogout}
                            className="text-sm text-muted-foreground hover:text-foreground underline underline-offset-4 bg-transparent border-0 cursor-pointer"
                        >
                            Log out
                        </button>
                    </div>
                </div>
            </form>
        </>
    );
}

BusinessVerifyPhone.layout = {
    title: 'Verify business phone number',
    description: 'Please enter the 6-digit verification code sent to your email to verify your business phone number.',
};
