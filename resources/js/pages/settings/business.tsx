import { Form, Head } from '@inertiajs/react';
import BusinessController from '@/actions/App/Http/Controllers/Settings/BusinessController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/business';

type Props = {
    business: {
        name: string;
        slug: string;
        timezone: string;
    };
    timezones: string[];
};

export default function Business({ business, timezones }: Props) {
    return (
        <>
            <Head title="Business settings" />

            <h1 className="sr-only">Business settings</h1>

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Business"
                    description="Your business name, booking page address and time zone"
                />

                <Form
                    {...BusinessController.update.form()}
                    options={{
                        preserveScroll: true,
                    }}
                    className="space-y-6"
                >
                    {({ processing, errors, recentlySuccessful }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Business name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={business.name}
                                    required
                                    autoComplete="organization"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="slug">
                                    Booking page address
                                </Label>
                                <div className="flex items-center gap-1 text-sm">
                                    <span className="text-muted-foreground">
                                        {window.location.host}/b/
                                    </span>
                                    <Input
                                        id="slug"
                                        name="slug"
                                        defaultValue={business.slug}
                                        required
                                        pattern="[a-z0-9]+(-[a-z0-9]+)*"
                                        title="Lowercase letters, numbers and single hyphens"
                                    />
                                </div>
                                <InputError message={errors.slug} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="timezone">Time zone</Label>
                                <select
                                    id="timezone"
                                    name="timezone"
                                    defaultValue={business.timezone}
                                    className="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm shadow-xs"
                                >
                                    {timezones.map((tz) => (
                                        <option key={tz} value={tz}>
                                            {tz.replaceAll('_', ' ')}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.timezone} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button
                                    disabled={processing}
                                    data-test="update-business-button"
                                >
                                    Save
                                </Button>
                                {recentlySuccessful && (
                                    <p className="text-sm text-muted-foreground">
                                        Saved.
                                    </p>
                                )}
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

Business.layout = {
    breadcrumbs: [
        {
            title: 'Business settings',
            href: edit(),
        },
    ],
};
