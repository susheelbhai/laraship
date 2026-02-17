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
        title: 'Shipping Providers',
        href: route('admin.shipping_provider.index'),
    },
];

export default function Index() {
    const data = ((usePage<SharedData>().props as any)?.data as any);
    const items = data?.data || [];

    const thead = [
        { title: 'Name', className: 'p-3' },
        { title: 'Adapter', className: 'p-3' },
        { title: 'Priority', className: 'p-3' },
        { title: 'Balance', className: 'p-3' },
        { title: 'Shipments', className: 'p-3' },
        { title: 'Status', className: 'p-3' },
        { title: 'View', className: 'p-3' },
    ];

    // Extract adapter name from class path
    const getAdapterName = (adapterClass: string) => {
        // Extract class name from full path (e.g., "Susheelbhai\Laraship\Adapters\BluedartAdapter" -> "Bluedart")
        const className = adapterClass.split('\\').pop() || '';
        return className.replace('Adapter', '');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Shipping Providers" />
            <ButtonCreate href={route('admin.shipping_provider.create')} text="Add Provider" />

            <TableCard>
                <Table>
                    <THead data={thead} />
                    <TBody>
                        {items.map((provider: any) => (
                            <tr key={provider.id} className="border-t border-gray-200">
                                <td className="p-3">{provider.name}</td>
                                <td className="p-3">{getAdapterName(provider.adapter_class)}</td>
                                <td className="p-3">{provider.priority}</td>
                                <td className="p-3">
                                    {provider.balance ? (
                                        <span className="text-green-600 font-medium">
                                            ₹{provider.balance.balance.toFixed(2)}
                                        </span>
                                    ) : (
                                        <span className="text-gray-400 text-sm">N/A</span>
                                    )}
                                </td>
                                <td className="p-3">{provider.shipments_count || 0}</td>
                                <td className="p-3">
                                    <span className={`px-2 py-1 rounded text-xs ${provider.is_enabled ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'}`}>
                                        {provider.is_enabled ? 'Enabled' : 'Disabled'}
                                    </span>
                                </td>
                                <td className="p-3">
                                    <TextLink href={route('admin.shipping_provider.show', provider.id)}>
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
