import { Head } from '@inertiajs/react';
import { FormContainer } from '@/components/form/container/form-container';
import { InputDiv } from '@/components/form/container/input-div';
import AppLayout from '@/layouts/admin/app-layout';
import { useFormHandler } from '@/lib/use-form-handler';
import { CredentialFields } from './credentials/CredentialFields';
import type { BreadcrumbItem } from '@/types';

type FormType = {
    name: string;
    display_name: string;
    adapter_class: string;
    credentials_api_key: string;
    credentials_secret_key: string;
    credentials_email: string;
    credentials_password: string;
    credentials_username: string;
    credentials_license_key: string;
    credentials_login_id: string;
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
        credentials_secret_key: '',
        credentials_email: '',
        credentials_password: '',
        credentials_username: '',
        credentials_license_key: '',
        credentials_login_id: '',
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

    // Debug: Log errors
    console.log('Form errors:', inputDivData.errors);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Shipping Provider" />
            <FormContainer onSubmit={submit} processing={processing} buttonLabel="Create Provider">
                {/* Display general errors */}
                {Object.keys(inputDivData.errors || {}).length > 0 && (
                    <div className="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
                        <p className="text-sm font-medium text-red-800 mb-2">Please fix the following errors:</p>
                        <ul className="list-disc list-inside text-sm text-red-600">
                            {Object.entries(inputDivData.errors).map(([field, messages]) => (
                                <li key={field}>
                                    {field}: {Array.isArray(messages) ? messages[0] : messages}
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

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

                {data.adapter_class && (
                    <CredentialFields adapterClass={data.adapter_class} inputDivData={inputDivData} />
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
                />
            </FormContainer>
        </AppLayout>
    );
}
