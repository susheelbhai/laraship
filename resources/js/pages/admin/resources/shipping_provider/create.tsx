import { Head } from '@inertiajs/react';
import { FormContainer } from '@/components/form/container/form-container';
import { InputDiv } from '@/components/form/container/input-div';
import AppLayout from '@/layouts/admin/app-layout';
import { useFormHandler } from '@/lib/use-form-handler';
import type { BreadcrumbItem } from '@/types';

type FormType = {
    name: string;
    display_name: string;
    adapter_class: string;
    credentials_api_key: string;
    credentials_api_secret: string;
    credentials_email: string;
    credentials_password: string;
    config: Record<string, string>;
    priority: number;
    tracking_url_template: string;
};

interface Adapter {
    value: string;
    label: string;
}

interface Props {
    availableAdapters: Adapter[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shipping Providers',
        href: route('admin.shipping_provider.index'),
    },
    {
        title: 'Create',
        href: route('admin.shipping_provider.create'),
    },
];

export default function Create({ availableAdapters }: Props) {
    const initialValues: FormType = {
        name: '',
        display_name: '',
        adapter_class: '',
        credentials_api_key: '',
        credentials_api_secret: '',
        credentials_email: '',
        credentials_password: '',
        config: {},
        priority: 0,
        tracking_url_template: '',
    };

    const { submit, inputDivData, processing, data } = useFormHandler<FormType>({
        url: route('admin.shipping_provider.store'),
        initialValues,
        method: 'POST',
        onSuccess: () => console.log('Shipping provider created successfully!'),
    });

    // Determine which credential fields to show based on adapter
    const requiresEmailPassword = data.adapter_class?.includes('Shiprocket');
    const requiresApiKey = !requiresEmailPassword;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Shipping Provider" />
            <FormContainer onSubmit={submit} processing={processing} buttonLabel="Create Provider">
                <InputDiv
                    type="text"
                    label="Provider Name (Unique Key)"
                    name="name"
                    inputDivData={inputDivData}
                    placeholder="e.g., delhivery"
                />
                <InputDiv
                    type="text"
                    label="Display Name"
                    name="display_name"
                    inputDivData={inputDivData}
                    placeholder="e.g., Delhivery"
                />
                <InputDiv
                    type="select"
                    label="Adapter Class"
                    name="adapter_class"
                    inputDivData={inputDivData}
                    options={availableAdapters}
                />
                {requiresApiKey && (
                    <>
                        <InputDiv
                            type="text"
                            label="API Key"
                            name="credentials_api_key"
                            inputDivData={inputDivData}
                            placeholder="Enter API Key"
                        />
                        <InputDiv
                            type="text"
                            label="API Secret (Optional)"
                            name="credentials_api_secret"
                            inputDivData={inputDivData}
                            placeholder="Enter API Secret"
                        />
                    </>
                )}
                {requiresEmailPassword && (
                    <>
                        <InputDiv
                            type="email"
                            label="Email"
                            name="credentials_email"
                            inputDivData={inputDivData}
                            placeholder="Enter email address"
                        />
                        <InputDiv
                            type="password"
                            label="Password"
                            name="credentials_password"
                            inputDivData={inputDivData}
                            placeholder="Enter password"
                        />
                    </>
                )}
                <InputDiv
                    type="number"
                    label="Priority (Lower = Higher Priority)"
                    name="priority"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="text"
                    label="Tracking URL Template"
                    name="tracking_url_template"
                    inputDivData={inputDivData}
                    placeholder="https://example.com/track/{tracking_number}"
                    // helperText="Use {tracking_number} as placeholder"
                />
            </FormContainer>
        </AppLayout>
    );
}
