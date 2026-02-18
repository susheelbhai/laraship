import EditRow from '@/components/table/edit-row';
import Table from '@/components/table/table';
import TableCard from '@/components/table/table-card';
import TBody from '@/components/table/tbody';
import THead from '@/components/table/thead';

interface ProviderDetailsTableProps {
    provider: {
        id: number;
        name: string;
        display_name: string;
        adapter_class: string;
        priority: number;
        is_enabled: boolean;
        tracking_url_template: string;
        shipments_count: number;
        booking_attempts_count: number;
        wallet_balance: { balance: number; currency: string; formatted: string } | null;
        created_at: string;
        updated_at: string;
    };
    onAddMoney: () => void;
}

export default function ProviderDetailsTable({ provider, onAddMoney }: ProviderDetailsTableProps) {
    const thead = [
        { title: 'Provider Detail', className: 'p-3' },
        { title: '', className: 'p-3' },
    ];

    return (
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
                                        onClick={onAddMoney}
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
    );
}
