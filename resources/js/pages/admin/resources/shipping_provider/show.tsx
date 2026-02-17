import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import EditRow from '@/components/table/edit-row';
import Table from '@/components/table/table';
import TableCard from '@/components/table/table-card';
import TBody from '@/components/table/tbody';
import THead from '@/components/table/thead';
import AppLayout from '@/layouts/admin/app-layout';
import RechargeModal from './RechargeModal';
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
        wallet_balance: { balance: number; currency: string; formatted: string } | null;
        supports_recharge: boolean;
        recharge_url: string | null;
        created_at: string;
        updated_at: string;
    }) || {};

    console.log('Provider data:', provider);
    console.log('Wallet balance:', provider.wallet_balance);
    console.log('Supports recharge:', provider.supports_recharge);
    console.log('Recharge URL:', provider.recharge_url);

    const [showRechargeModal, setShowRechargeModal] = useState(false);

    const handleAddMoney = () => {
        // If provider doesn't support API recharge, redirect to their website directly
        if (!provider.supports_recharge && provider.recharge_url) {
            window.open(provider.recharge_url, '_blank');
            return;
        }
        
        // Otherwise show the modal for API recharge
        setShowRechargeModal(true);
    };

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
                        {provider.wallet_balance && (
                            <tr className="border-t border-gray-200 bg-blue-50">
                                <td className="p-3 font-medium">Wallet Balance</td>
                                <td className="p-3">
                                    <div className="flex items-center gap-3">
                                        <span className="text-lg font-semibold text-blue-900">
                                            {provider.wallet_balance.formatted}
                                        </span>
                                        <button
                                            onClick={handleAddMoney}
                                            className="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700 transition"
                                        >
                                            Add Money
                                        </button>
                                    </div>
                                    {provider.wallet_balance.balance < 1000 && (
                                        <p className="mt-1 text-sm text-red-600">
                                            ⚠️ Low balance - Consider recharging
                                        </p>
                                    )}
                                </td>
                            </tr>
                        )}
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

            <RechargeModal
                isOpen={showRechargeModal}
                onClose={() => setShowRechargeModal(false)}
                providerId={provider.id}
                currency={provider.wallet_balance?.currency}
                rechargeUrl={provider.recharge_url}
            />
        </AppLayout>
    );
}
