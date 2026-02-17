import { Head, usePage } from '@inertiajs/react';
import { useState } from 'react';
import axios from 'axios';
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
        wallet_balance: { balance: number; currency: string; formatted: string } | null;
        created_at: string;
        updated_at: string;
    }) || {};

    const [showRechargeModal, setShowRechargeModal] = useState(false);
    const [rechargeAmount, setRechargeAmount] = useState('');
    const [processing, setProcessing] = useState(false);
    const [rechargeResult, setRechargeResult] = useState<any>(null);
    const [error, setError] = useState<string | null>(null);

    const handleRecharge = async (e: React.FormEvent) => {
        e.preventDefault();
        setProcessing(true);
        setError(null);

        try {
            const response = await axios.post(
                route('admin.shipping_provider.recharge', provider.id),
                { amount: parseFloat(rechargeAmount) }
            );

            setRechargeResult(response.data.data);

            // If payment URL is provided, redirect
            if (response.data.data.payment_url) {
                window.location.href = response.data.data.payment_url;
            } else {
                // Close modal after 2 seconds and reload page
                setTimeout(() => {
                    window.location.reload();
                }, 2000);
            }
        } catch (err: any) {
            setError(err.response?.data?.message || 'Failed to recharge wallet');
        } finally {
            setProcessing(false);
        }
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
                                            onClick={() => setShowRechargeModal(true)}
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

            {/* Recharge Modal */}
            {showRechargeModal && (
                <div className="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                    <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                        <h3 className="text-lg font-semibold mb-4">Recharge Wallet</h3>

                        {!rechargeResult ? (
                            <form onSubmit={handleRecharge}>
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Amount ({provider.wallet_balance?.currency || 'INR'})
                                    </label>
                                    <input
                                        type="number"
                                        min="100"
                                        step="0.01"
                                        value={rechargeAmount}
                                        onChange={(e) => setRechargeAmount(e.target.value)}
                                        className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        placeholder="Enter amount"
                                        required
                                        disabled={processing}
                                    />
                                    <p className="mt-1 text-xs text-gray-500">Minimum: 100</p>
                                </div>

                                {error && (
                                    <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded-md">
                                        <p className="text-sm text-red-600">{error}</p>
                                    </div>
                                )}

                                <div className="flex gap-3">
                                    <button
                                        type="button"
                                        onClick={() => {
                                            setShowRechargeModal(false);
                                            setRechargeAmount('');
                                            setError(null);
                                        }}
                                        className="flex-1 px-4 py-2 border border-gray-300 rounded-md hover:bg-gray-50 transition"
                                        disabled={processing}
                                    >
                                        Cancel
                                    </button>
                                    <button
                                        type="submit"
                                        className="flex-1 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition disabled:opacity-50"
                                        disabled={processing}
                                    >
                                        {processing ? 'Processing...' : 'Recharge'}
                                    </button>
                                </div>
                            </form>
                        ) : (
                            <div className="space-y-4">
                                <div className="p-4 bg-green-50 border border-green-200 rounded-md">
                                    <h4 className="font-medium text-green-800 mb-2">Recharge Successful!</h4>
                                    <dl className="space-y-1 text-sm text-green-700">
                                        <div className="flex justify-between">
                                            <dt className="font-medium">Transaction ID:</dt>
                                            <dd>{rechargeResult.transaction_id}</dd>
                                        </div>
                                        <div className="flex justify-between">
                                            <dt className="font-medium">Amount:</dt>
                                            <dd>{provider.wallet_balance?.currency} {rechargeResult.amount}</dd>
                                        </div>
                                        <div className="flex justify-between">
                                            <dt className="font-medium">Status:</dt>
                                            <dd className="capitalize">{rechargeResult.status}</dd>
                                        </div>
                                    </dl>
                                </div>
                                <p className="text-sm text-gray-600 text-center">
                                    Redirecting...
                                </p>
                            </div>
                        )}
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
