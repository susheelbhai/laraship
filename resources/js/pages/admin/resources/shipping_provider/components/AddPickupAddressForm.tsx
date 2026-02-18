interface AddPickupAddressFormProps {
    availableAddresses: any[];
    selectedAddressId: string;
    onSelectedAddressChange: (id: string) => void;
    onSubmit: (e: React.FormEvent) => void;
    isSubmitting: boolean;
    error: string | null;
}

export default function AddPickupAddressForm({
    availableAddresses,
    selectedAddressId,
    onSelectedAddressChange,
    onSubmit,
    isSubmitting,
    error
}: AddPickupAddressFormProps) {
    return (
        <div className="mb-6 p-4 border border-gray-200 rounded-lg bg-gray-50">
            <h3 className="text-lg font-medium mb-4">Add Pickup Address from Database</h3>
            {error && (
                <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700">
                    {error}
                </div>
            )}
            {availableAddresses.length === 0 ? (
                <div className="text-center py-4 text-gray-500">
                    <p>No available pickup addresses to add.</p>
                    <p className="text-sm mt-2">All addresses are already added or no addresses exist in the database.</p>
                    <a 
                        href={route('admin.pickup_address.create')} 
                        className="inline-block mt-3 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Create New Pickup Address
                    </a>
                </div>
            ) : (
                <form onSubmit={onSubmit} className="space-y-4">
                    <div>
                        <label className="block text-sm font-medium mb-2">Select Pickup Address *</label>
                        <select
                            required
                            value={selectedAddressId}
                            onChange={(e) => onSelectedAddressChange(e.target.value)}
                            className="w-full px-3 py-2 border border-gray-300 rounded"
                        >
                            <option value="">-- Select an address --</option>
                            {availableAddresses.map((address: any) => (
                                <option key={address.id} value={address.id}>
                                    {address.name} - {address.city}, {address.state} ({address.pincode})
                                </option>
                            ))}
                        </select>
                        <p className="mt-1 text-sm text-gray-500">
                            This will create the address in the provider's system.
                        </p>
                        <p className="mt-1 text-sm text-amber-600">
                            Note: Some providers (like Shiprocket) require address line 1 to include house/flat/road number.
                        </p>
                    </div>
                    <button
                        type="submit"
                        disabled={isSubmitting || !selectedAddressId}
                        className="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                    >
                        {isSubmitting ? 'Adding...' : 'Add Pickup Address'}
                    </button>
                </form>
            )}
        </div>
    );
}
