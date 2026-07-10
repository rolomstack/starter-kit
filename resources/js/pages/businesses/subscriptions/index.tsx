import { Head, router } from '@inertiajs/react';
import React from 'react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardHeader,
    CardTitle,
    CardDescription,
    CardContent,
    CardFooter,
} from '@/components/ui/card';
import { edit as businessEdit } from '@/routes/businesses';
import { checkout, cancel } from '@/routes/businesses/subscriptions';

interface PlanFeature {
    id: number;
    name: string;
    value: string | null;
}

interface Plan {
    id: number;
    name: string;
    description: string;
    price: number;
    currency: string;
    interval: string;
    features: PlanFeature[];
}

interface Subscription {
    id: number;
    plan_id: number;
    status: string;
    ends_at: string | null;
    trial_ends_at: string | null;
    plan?: Plan;
}

interface Business {
    id: number;
    name: string;
    slug: string;
}

interface Props {
    business: Business;
    plans: Plan[];
    activeSubscription: Subscription | null;
}

export default function SubscriptionsIndex({
    business,
    plans,
    activeSubscription,
}: Props) {
    const handleSubscribe = (planId: number) => {
        router.post(checkout(business.slug).url, {
            plan_id: planId,
        });
    };

    const handleCancel = () => {
        if (
            confirm('Are you sure you want to cancel your active subscription?')
        ) {
            router.post(cancel(business.slug).url);
        }
    };

    return (
        <>
            <Head title="Subscriptions" />

            <div className="flex flex-col space-y-10">
                <div className="space-y-6">
                    <Heading
                        variant="small"
                        title="Subscriptions"
                        description="Manage your subscription and billing"
                    />

                    {activeSubscription && (
                        <div className="mb-8">
                            <Card className="border-primary/20 bg-muted/50">
                                <CardHeader>
                                    <CardTitle>Current Subscription</CardTitle>
                                    <CardDescription>
                                        Manage your active plan for{' '}
                                        {business.name}.
                                    </CardDescription>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex items-center space-x-4">
                                        <div className="text-xl font-semibold">
                                            {activeSubscription.plan?.name}
                                        </div>
                                        <Badge
                                            variant={
                                                activeSubscription.status ===
                                                'active'
                                                    ? 'default'
                                                    : 'destructive'
                                            }
                                        >
                                            {activeSubscription.status}
                                        </Badge>
                                    </div>
                                    {activeSubscription.ends_at && (
                                        <p className="mt-2 text-sm text-muted-foreground">
                                            Your subscription will end on{' '}
                                            {new Date(
                                                activeSubscription.ends_at,
                                            ).toLocaleDateString()}
                                            .
                                        </p>
                                    )}
                                </CardContent>
                                <CardFooter>
                                    {activeSubscription.status === 'active' &&
                                        !activeSubscription.ends_at && (
                                            <Button
                                                variant="destructive"
                                                onClick={handleCancel}
                                            >
                                                Cancel Subscription
                                            </Button>
                                        )}
                                </CardFooter>
                            </Card>
                        </div>
                    )}

                    <div className="mb-6">
                        <h2 className="text-xl font-bold tracking-tight">
                            Available Plans
                        </h2>
                        <p className="mt-1 text-sm text-muted-foreground">
                            Select the best plan that fits your business needs.
                        </p>
                    </div>

                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        {plans.map((plan) => (
                            <Card
                                key={plan.id}
                                className={`flex flex-col ${activeSubscription?.plan_id === plan.id ? 'border-primary ring-1 ring-primary' : ''}`}
                            >
                                <CardHeader>
                                    <CardTitle className="text-xl">
                                        {plan.name}
                                    </CardTitle>
                                    <CardDescription>
                                        {plan.description}
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="flex-grow">
                                    <div className="mb-6">
                                        <span className="text-4xl font-extrabold text-foreground">
                                            {plan.currency === 'NGN'
                                                ? '₦'
                                                : plan.currency}
                                            {(
                                                plan.price / 100
                                            ).toLocaleString()}
                                        </span>
                                        <span className="font-medium text-muted-foreground">
                                            /
                                            {plan.interval === 'monthly'
                                                ? 'mo'
                                                : 'yr'}
                                        </span>
                                    </div>
                                    <ul className="space-y-2">
                                        {plan.features?.map((feature) => (
                                            <li
                                                key={feature.id}
                                                className="flex items-center text-sm"
                                            >
                                                <svg
                                                    className="mr-2 h-4 w-4 text-primary"
                                                    fill="none"
                                                    strokeLinecap="round"
                                                    strokeLinejoin="round"
                                                    strokeWidth="2"
                                                    viewBox="0 0 24 24"
                                                    stroke="currentColor"
                                                >
                                                    <path d="M5 13l4 4L19 7"></path>
                                                </svg>
                                                {feature.name}{' '}
                                                {feature.value && (
                                                    <span className="ml-1 text-muted-foreground">
                                                        ({feature.value})
                                                    </span>
                                                )}
                                            </li>
                                        ))}
                                    </ul>
                                </CardContent>
                                <CardFooter>
                                    {activeSubscription?.plan_id === plan.id ? (
                                        <Button className="w-full" disabled>
                                            Current Plan
                                        </Button>
                                    ) : (
                                        <Button
                                            className="w-full"
                                            onClick={() =>
                                                handleSubscribe(plan.id)
                                            }
                                        >
                                            {activeSubscription
                                                ? 'Switch Plan'
                                                : 'Subscribe'}
                                        </Button>
                                    )}
                                </CardFooter>
                            </Card>
                        ))}
                    </div>
                </div>
            </div>
        </>
    );
}

SubscriptionsIndex.layout = (props: {
    business: { name: string; slug: string };
}) => ({
    breadcrumbs: [
        {
            title: props.business.name,
            href: businessEdit(props.business.slug),
        },
        {
            title: 'Subscriptions',
        },
    ],
});
