interface PickupAddressesTableProps {
    addresses: any[];
    onEdit: (address: any) => void;
    onDelete: (addressId: string) => void;
    deletingAddressId: string | null;
}

export default function PickupAddressesTable({
    addresses,
    onEdit,
    onDelete,
    deletingAddressId
}: PickupAddressesTableProps) {
    return (
        <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
                <thead className="bg-gray-50">
                    <tr>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Phone</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Address</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">City</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Pincode</th>
                        <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody className="bg-white divide-y divide-gray-200">
                    {addresses.map((address: any, index: number) => (
                        <tr key={index}>
                            <td className="px-6 py-4 whitespace-nowrap text-sm">{address.name || 'N/A'}</td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm">{address.phone || 'N/A'}</td>
                            <td className="px-6 py-4 text-sm">{address.address || address.address_line1 || 'N/A'}</td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm">{address.city || 'N/A'}</td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm">{address.pincode || address.pin_code || 'N/A'}</td>
                            <td className="px-6 py-4 whitespace-nowrap text-sm">
                                <div className="flex gap-2">
                                    <button
                                        onClick={() => onEdit(address)}
                                        className="px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700 text-xs"
                                    >
                                        Edit
                                    </button>
                                    <button
                                        onClick={() => onDelete(address.id)}
                                        disabled={deletingAddressId === address.id}
                                        className="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 disabled:opacity-50 text-xs"
                                    >
                                        {deletingAddressId === address.id ? 'Deleting...' : 'Delete'}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}
