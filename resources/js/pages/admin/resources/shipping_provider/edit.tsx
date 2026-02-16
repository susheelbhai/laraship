import { Head, usePage } from '@inertiajs/react';
import { FormContainer } from '@/components/form/container/form-container';
import { InputDiv } from '@/components/form/container/input-div';
import AppLayout from '@/layouts/admin/app-layout';
import { useFormHandler } from '@/lib/use-form-handler';
import type { BreadcrumbItem, SharedData } from '@/types';

type FormType = {
    id: number;
    display_name: string;
    credentials_api_key: string;
    credentials_api_secret: string;
    config: Record<string, string>;
    is_enabled: boolean;
    priority: number;
    tracking_url_template: string;
};

type ProviderData = {
    id: number;
    display_name: string;
    credentials: {
        api_key: string;
        api_secret: string;
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
        credentials_api_secret: provider?.credentials?.api_secret || '',
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

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Shipping Provider" />
            <FormContainer onSubmit={submit} processing={processing} buttonLabel="Update Provider">
                <InputDiv
                    type="text"
                    label="Display Name"
                    name="display_name"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="text"
                    label="API Key"
                    name="credentials_api_key"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="text"
                    label="API Secret (Optional)"
                    name="credentials_api_secret"
                    inputDivData={inputDivData}
                />
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
