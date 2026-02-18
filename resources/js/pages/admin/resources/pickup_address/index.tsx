import { Head, usePage } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import Pagination from '@/components/table/pagination';
import Table from '@/components/table/table';
import TableCard from '@/components/table/table-card';
import TBody from '@/components/table/tbody';
import THead from '@/components/table/thead';
import TextLink from '@/components/text-link';
import ButtonCreate from '@/components/ui/button-create';
import AppLayout from '@/layouts/admin/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Pickup Addresses',
        href: route('admin.pickup_address.index'),
    },
];

export default function Index() {
    const data = ((usePage<SharedData>().props as any)?.data as any);
    const items = data?.data || [];

    const thead = [
        { title: 'Name', className: 'p-3' },
        { title: 'Phone', className: 'p-3' },
        { title: 'City', className: 'p-3' },
        { title: 'State', className: 'p-3' },
        { title: 'Pincode', className: 'p-3' },
        { title: 'Default', className: 'p-3' },
        { title: 'Status', className: 'p-3' },
        { title: 'View', className: 'p-3' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pickup Addresses" />
            <ButtonCreate href={route('admin.pickup_address.create')} text="Add Address" />

            <TableCard>
                <Table>
                    <THead data={thead} />
                    <TBody>
                        {items.map((address: any) => (
                            <tr key={address.id} className="border-t border-gray-200">
                                <td className="p-3">{address.name}</td>
                                <td className="p-3">{address.phone}</td>
                                <td className="p-3">{address.city}</td>
                                <td className="p-3">{address.state}</td>
                                <td className="p-3">{address.pincode}</td>
                                <td className="p-3">
                                    {address.is_default && (
                                        <span className="px-2 py-1 rounded text-xs bg-blue-100 text-blue-800">
                                            Default
                                        </span>
                                    )}
                                </td>
                                <td className="p-3">
                                    <span className={`px-2 py-1 rounded text-xs ${address.is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                        {address.is_active ? 'Active' : 'Inactive'}
                                    </span>
                                </td>
                                <td className="p-3">
                                    <TextLink href={route('admin.pickup_address.show', address.id)}>
                                        <Eye className="h-4 w-4" />
                                    </TextLink>
                                </td>
                            </tr>
                        ))}
                    </TBody>
                </Table>
                <Pagination data={data} />
            </TableCard>
        </AppLayout>
    );
}
