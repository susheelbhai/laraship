import { Head, usePage } from '@inertiajs/react';
import { FormContainer } from '@/components/form/container/form-container';
import { InputDiv } from '@/components/form/container/input-div';
import AppLayout from '@/layouts/admin/app-layout';
import { useFormHandler } from '@/lib/use-form-handler';
import type { BreadcrumbItem, SharedData } from '@/types';

type FormType = {
    id: number;
    name: string;
    phone: string;
    email: string;
    address_line1: string;
    address_line2: string;
    city: string;
    state: string;
    pincode: string;
    country: string;
    is_default: boolean;
    is_active: boolean;
};

type AddressData = {
    id: number;
    name: string;
    phone: string;
    email: string;
    address_line1: string;
    address_line2: string;
    city: string;
    state: string;
    pincode: string;
    country: string;
    is_default: boolean;
    is_active: boolean;
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pickup Addresses',
        href: route('admin.pickup_address.index'),
    },
    {
        title: 'Edit',
        href: '#',
    },
];

export default function Edit() {
    const address = ((usePage<SharedData>().props as any)?.data as AddressData) || {} as AddressData;

    const initialValues: FormType = {
        id: address?.id || 0,
        name: address?.name || '',
        phone: address?.phone || '',
        email: address?.email || '',
        address_line1: address?.address_line1 || '',
        address_line2: address?.address_line2 || '',
        city: address?.city || '',
        state: address?.state || '',
        pincode: address?.pincode || '',
        country: address?.country || 'India',
        is_default: address?.is_default ?? false,
        is_active: address?.is_active ?? true,
    };

    const { submit, inputDivData, processing } = useFormHandler<FormType>({
        url: route('admin.pickup_address.update', address?.id),
        initialValues,
        method: 'PUT',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Edit Pickup Address" />
            <FormContainer onSubmit={submit} processing={processing} buttonLabel="Update Address">
                <InputDiv
                    type="text"
                    label="Contact Name"
                    name="name"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="text"
                    label="Phone"
                    name="phone"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="email"
                    label="Email"
                    name="email"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="text"
                    label="Address Line 1"
                    name="address_line1"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="text"
                    label="Address Line 2"
                    name="address_line2"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="text"
                    label="City"
                    name="city"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="text"
                    label="State"
                    name="state"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="text"
                    label="Pincode"
                    name="pincode"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="text"
                    label="Country"
                    name="country"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="switch"
                    label="Set as Default"
                    name="is_default"
                    inputDivData={inputDivData}
                />
                <InputDiv
                    type="switch"
                    label="Active"
                    name="is_active"
                    inputDivData={inputDivData}
                />
            </FormContainer>
        </AppLayout>
    );
}
