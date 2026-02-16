import { Head, usePage } from '@inertiajs/react';
import EditRow from '@/components/table/edit-row';
import Table from '@/components/table/table';
import TableCard from '@/components/table/table-card';
import TBody from '@/components/table/tbody';
import THead from '@/components/table/thead';
import AppLayout from '@/layouts/admin/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Shipping Providers',
        href: route('admin.shipping_provider.index'),
    },
    {
        title: 'Provider Detail',
        href: '#',
    },
];

export default function Show() {
    const provider = ((usePage<SharedData>().props as any)?.data as {
        id: number;
        name: string;
        display_name: string;
        adapter_class: string;
        is_enabled: boolean;
        priority: number;
        tracking_url_template: string;
        shipments_count: number;
        booking_attempts_count: number;
        created_at: string;
        updated_at: string;
    }) || {};

    const thead = [
        { title: 'Provider Detail', className: 'p-3' },
        { title: '', className: 'p-3' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Provider Detail" />

            <TableCard>
                <Table>
                    <THead data={thead} />
                    <TBody>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Name</td>
                            <td className="p-3">{provider.name}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Display Name</td>
                            <td className="p-3">{provider.display_name}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Adapter Class</td>
                            <td className="p-3">{provider.adapter_class}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Priority</td>
                            <td className="p-3">{provider.priority}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Status</td>
                            <td className="p-3">
                                <span className={`px-2 py-1 rounded text-xs ${provider.is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                    {provider.is_enabled ? 'Enabled' : 'Disabled'}
                                </span>
                            </td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Tracking URL Template</td>
                            <td className="p-3">{provider.tracking_url_template || 'Not set'}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Total Shipments</td>
                            <td className="p-3">{provider.shipments_count || 0}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Booking Attempts</td>
                            <td className="p-3">{provider.booking_attempts_count || 0}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Created At</td>
                            <td className="p-3">{new Date(provider.created_at).toLocaleString()}</td>
                        </tr>
                        <tr className="border-y border-gray-200">
                            <td className="p-3">Updated At</td>
                            <td className="p-3">{new Date(provider.updated_at).toLocaleString()}</td>
                        </tr>
                        <EditRow href={route('admin.shipping_provider.edit', provider.id)} buttonName='Edit Provider' />
                    </TBody>
                </Table>
            </TableCard>
        </AppLayout>
    );
}
