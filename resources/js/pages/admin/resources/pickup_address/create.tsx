import { Head } from '@inertiajs/react';
import { FormContainer } from '@/components/form/container/form-container';
import { InputDiv } from '@/components/form/container/input-div';
import AppLayout from '@/layouts/admin/app-layout';
import { useFormHandler } from '@/lib/use-form-handler';
import type { BreadcrumbItem } from '@/types';

type FormType = {
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
        title: 'Create',
        href: route('admin.pickup_address.create'),
    },
];

export default function Create() {
    const initialValues: FormType = {
        name: '',
        phone: '',
        email: '',
        address_line1: '',
        address_line2: '',
        city: '',
        state: '',
        pincode: '',
        country: 'India',
        is_default: false,
        is_active: true,
    };

    const { submit, inputDivData, processing } = useFormHandler<FormType>({
        url: route('admin.pickup_address.store'),
        initialValues,
        method: 'POST',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Pickup Address" />
            <FormContainer onSubmit={submit} processing={processing} buttonLabel="Create Address">
                <InputDiv
                    type="text"
                    label="Contact Name"
                    name="name"
                    inputDivData={inputDivData}
                    placeholder="e.g., John Doe"
                />
                <InputDiv
                    type="text"
                    label="Phone"
                    name="phone"
                    inputDivData={inputDivData}
                    placeholder="e.g., +91 9876543210"
                />
                <InputDiv
                    type="email"
                    label="Email"
                    name="email"
                    inputDivData={inputDivData}
                    placeholder="e.g., contact@example.com"
                />
                <InputDiv
                    type="text"
                    label="Address Line 1"
                    name="address_line1"
                    inputDivData={inputDivData}
                    placeholder="e.g., Building No, Street Name"
                />
                <InputDiv
                    type="text"
                    label="Address Line 2"
                    name="address_line2"
                    inputDivData={inputDivData}
                    placeholder="e.g., Landmark (Optional)"
                />
                <InputDiv
                    type="text"
                    label="City"
                    name="city"
                    inputDivData={inputDivData}
                    placeholder="e.g., Mumbai"
                />
                <InputDiv
                    type="text"
                    label="State"
                    name="state"
                    inputDivData={inputDivData}
                    placeholder="e.g., Maharashtra"
                />
                <InputDiv
                    type="text"
                    label="Pincode"
                    name="pincode"
                    inputDivData={inputDivData}
                    placeholder="e.g., 400001"
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
