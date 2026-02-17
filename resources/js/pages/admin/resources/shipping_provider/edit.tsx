import { Head, usePage } from '@inertiajs/react';
import { FormContainer } from '@/components/form/container/form-container';
import { InputDiv } from '@/components/form/container/input-div';
import AppLayout from '@/layouts/admin/app-layout';
import { useFormHandler } from '@/lib/use-form-handler';
import { CredentialFields } from './credentials/CredentialFields';
import type { BreadcrumbItem, SharedData } from '@/types';

type FormType = {
    id: number;
    display_name: string;
    credentials_api_key: string;
    credentials_secret_key: string;
    credentials_email: string;
    credentials_password: string;
    credentials_username: string;
    credentials_license_key: string;
    credentials_login_id: string;
    config: Record<string, string>;
    is_enabled: boolean;
    priority: number;
    tracking_url_template: string;
};

type ProviderData = {
    id: number;
    name: string;
    display_name: string;
    adapter_class: string;
    credentials: {
        api_key?: string;
        secret_key?: string;
        email?: string;
        password?: string;
        username?: string;
        license_key?: string;
        login_id?: string;
    };
    config: Record<string, string>;
    is_enabled: boolean;
    priority: number;
    tracking_url_template: string;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shipping Providers',
        href: route('admin.shipping_provider.index'),
    },
    {
        title: 'Edit',
        href: '#',
    },
];

export default function Edit() {
    const provider = ((usePage<SharedData>().props as any)?.data as ProviderData) || {} as ProviderData;

    const initialValues: FormType = {
        id: provider?.id || 0,
        display_name: provider?.display_name || '',
        credentials_api_key: provider?.credentials?.api_key || '',
        credentials_secret_key: provider?.credentials?.secret_key || '',
        credentials_email: provider?.credentials?.email || '',
        credentials_password: provider?.credentials?.password || '',
        credentials_username: provider?.credentials?.username || '',
        credentials_license_key: provider?.credentials?.license_key || '',
        credentials_login_id: provider?.credentials?.login_id || '',
        config: provider?.config || {},
        is_enabled: provider?.is_enabled ?? false,
        priority: provider?.priority || 0,
        tracking_url_template: provider?.tracking_url_template || '',
    };

    const { submit, inputDivData, processing } = useFormHandler<FormType>({
        url: route('admin.shipping_provider.update', provider?.id),
        initialValues,
        method: 'PUT',
        onSuccess: () => console.log('Shipping provider updated successfully!'),
    });

    // Debug: Log errors
    console.log('Form errors:', inputDivData.errors);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Shipping Provider" />
            <FormContainer onSubmit={submit} processing={processing} buttonLabel="Update Provider">
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
                    label="Display Name"
                    name="display_name"
                    inputDivData={inputDivData}
                />

                {provider?.adapter_class && (
                    <CredentialFields adapterClass={provider.adapter_class} inputDivData={inputDivData} />
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
                <InputDiv
                    type="switch"
                    label="Enabled"
                    name="is_enabled"
                    inputDivData={inputDivData}
                />
            </FormContainer>
        </AppLayout>
    );
}
