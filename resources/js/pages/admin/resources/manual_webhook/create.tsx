import { Head } from '@inertiajs/react';
import { FormContainer } from '@/components/form/container/form-container';
import { InputDiv } from '@/components/form/container/input-div';
import AppLayout from '@/layouts/admin/app-layout';
import { useFormHandler } from '@/lib/use-form-handler';
import type { BreadcrumbItem } from '@/types';
import { AlertCircle } from 'lucide-react';

type FormType = {
    tracking_number: string;
    status: string;
    location: string;
    description: string;
    occurred_at: string;
};

interface Shipment {
    id: number;
    tracking_number: string;
    order_id: number;
    current_status: string;
    provider_name: string;
}

interface StatusOption {
    value: string;
    name: string;
}

interface Props {
    shipments: Shipment[];
    statusOptions: StatusOption[];
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Manual Webhook',
        href: route('admin.manual_webhook.create'),
    },
];

export default function Create({ shipments, statusOptions }: Props) {
    const initialValues: FormType = {
        tracking_number: '',
        status: '',
        location: '',
        description: '',
        occurred_at: '',
    };

    const { submit, inputDivData, processing } = useFormHandler<FormType>({
        url: route('admin.manual_webhook.send'),
        initialValues,
        method: 'POST',
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Manual Webhook Testing" />

            {/* Warning Banner */}
            <div className="mb-6 bg-yellow-50 border-l-4 border-yellow-400 p-4">
                <div className="flex">
                    <div className="flex-shrink-0">
                        <AlertCircle className="h-5 w-5 text-yellow-400" />
                    </div>
                    <div className="ml-3">
                        <h3 className="text-sm font-medium text-yellow-800">
                            Testing Feature - Mock Provider Only
                        </h3>
                        <div className="mt-2 text-sm text-yellow-700">
                            <p>
                                This feature is for testing webhooks with the Mock provider only. It
                                simulates webhook calls from shipping providers to test your webhook
                                handling without using real provider APIs.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* No Shipments Warning */}
            {shipments.length === 0 && (
                <div className="mb-6 bg-blue-50 border-l-4 border-blue-400 p-4">
                    <div className="flex">
                        <div className="flex-shrink-0">
                            <AlertCircle className="h-5 w-5 text-blue-400" />
                        </div>
                        <div className="ml-3">
                            <h3 className="text-sm font-medium text-blue-800">
                                No Mock Shipments Found
                            </h3>
                            <div className="mt-2 text-sm text-blue-700">
                                <p>
                                    You need to create a Mock provider and book a shipment first.
                                    Go to Shipping Providers → Create → Select "Mock Provider
                                    (Testing)" and book a test shipment.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <FormContainer
                onSubmit={submit}
                processing={processing || shipments.length === 0}
                buttonLabel="Send Webhook"
            >
                <InputDiv
                    type="select"
                    label="Tracking Number"
                    name="tracking_number"
                    inputDivData={inputDivData}
                    options={shipments.map((shipment) => ({
                        value: shipment.tracking_number,
                        name: `${shipment.tracking_number} (Order #${shipment.order_id} - Current: ${shipment.current_status})`,
                    }))}
                    required
                />

                <InputDiv
                    type="select"
                    label="New Status"
                    name="status"
                    inputDivData={inputDivData}
                    options={statusOptions}
                    required
                />

                <InputDiv
                    type="text"
                    label="Location"
                    name="location"
                    inputDivData={inputDivData}
                    placeholder="e.g., Mumbai Hub, Delhi Warehouse"
                />

                <InputDiv
                    type="textarea"
                    label="Description"
                    name="description"
                    inputDivData={inputDivData}
                    placeholder="e.g., Package picked up from warehouse"
                />

                <InputDiv
                    type="datetime-local"
                    label="Occurred At"
                    name="occurred_at"
                    inputDivData={inputDivData}
                />
            </FormContainer>

            {/* Info Section */}
            <div className="mt-6 bg-gray-50 border border-gray-200 rounded-lg p-4">
                <h3 className="text-sm font-medium text-gray-900 mb-2">How it works:</h3>
                <ol className="list-decimal list-inside text-sm text-gray-600 space-y-1">
                    <li>Select a shipment with Mock provider</li>
                    <li>Choose the new status you want to simulate</li>
                    <li>Optionally add location and description</li>
                    <li>Click "Send Webhook" to trigger the webhook</li>
                    <li>The webhook will be processed just like a real provider webhook</li>
                    <li>Check the shipment status and history to see the update</li>
                </ol>
            </div>

            {/* Available Shipments */}
            {shipments.length > 0 && (
                <div className="mt-6">
                    <h3 className="text-lg font-medium text-gray-900 mb-4">
                        Available Mock Shipments
                    </h3>
                    <div className="bg-white shadow overflow-hidden sm:rounded-md">
                        <ul className="divide-y divide-gray-200">
                            {shipments.map((shipment) => (
                                <li key={shipment.id} className="px-4 py-4">
                                    <div className="flex items-center justify-between">
                                        <div>
                                            <p className="text-sm font-medium text-gray-900">
                                                {shipment.tracking_number}
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                Order #{shipment.order_id} • Provider:{' '}
                                                {shipment.provider_name}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {shipment.current_status.replace('_', ' ').toUpperCase()}
                                            </span>
                                        </div>
                                    </div>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
