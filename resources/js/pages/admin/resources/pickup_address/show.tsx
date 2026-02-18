import { Head, usePage, router } from '@inertiajs/react';
import EditRow from '@/components/table/edit-row';
import Table from '@/components/table/table';
import TableCard from '@/components/table/table-card';
import TBody from '@/components/table/tbody';
import THead from '@/components/table/thead';
import AppLayout from '@/layouts/admin/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pickup Addresses',
        href: route('admin.pickup_address.index'),
    },
    {
        title: 'Address Detail',
        href: '#',
    },
];

export default function Show() {
    const address = ((usePage<SharedData>().props as any)?.data as {
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
        full_address: string;
        is_default: boolean;
        is_active: boolean;
        created_at: string;
        updated_at: string;
    }) || {};

    const handleToggleStatus = () => {
        router.post(route('admin.pickup_address.toggle', address.id), {}, {
            preserveScroll: true,
        });
    };

    const thead = [
        { title: 'Address Detail', className: 'p-3' },
        { title: '', className: 'p-3' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Address Detail" />

            <TableCard>
                <Table>
                    <THead data={thead} />
                    <TBody>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Contact Name</td>
                            <td className="p-3">{address.name}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Phone</td>
                            <td className="p-3">{address.phone}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Email</td>
                            <td className="p-3">{address.email || 'N/A'}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Full Address</td>
                            <td className="p-3">{address.full_address}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">City</td>
                            <td className="p-3">{address.city}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">State</td>
                            <td className="p-3">{address.state}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Pincode</td>
                            <td className="p-3">{address.pincode}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Country</td>
                            <td className="p-3">{address.country}</td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Default Address</td>
                            <td className="p-3">
                                {address.is_default ? (
                                    <span className="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
                                        Yes
                                    </span>
                                ) : (
                                    <span className="px-2 py-1 rounded text-xs bg-gray-100 text-gray-800">
                                        No
                                    </span>
                                )}
                            </td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Status</td>
                            <td className="p-3">
                                <div className="flex items-center gap-3">
                                    <span className={`px-2 py-1 rounded text-xs ${address.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                        {address.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                    <button
                                        onClick={handleToggleStatus}
                                        className="px-3 py-1 text-sm bg-gray-600 text-white rounded hover:bg-gray-700 transition"
                                    >
                                        Toggle Status
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <tr className="border-t border-gray-200">
                            <td className="p-3">Created At</td>
                            <td className="p-3">{new Date(address.created_at).toLocaleString()}</td>
                        </tr>
                        <tr className="border-y border-gray-200">
                            <td className="p-3">Updated At</td>
                            <td className="p-3">{new Date(address.updated_at).toLocaleString()}</td>
                        </tr>
                        <EditRow href={route('admin.pickup_address.edit', address.id)} buttonName='Edit Address' />
                    </TBody>
                </Table>
            </TableCard>
        </AppLayout>
    );
}
