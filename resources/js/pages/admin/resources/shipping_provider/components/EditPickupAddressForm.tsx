interface EditPickupAddressFormProps {
    formData: {
        name: string;
        contact_name: string;
        phone: string;
        email: string;
        address: string;
        address_2: string;
        city: string;
        state: string;
        pincode: string;
        country: string;
        company_name: string;
        gstin: string;
    };
    onFormDataChange: (data: any) => void;
    onSubmit: (e: React.FormEvent) => void;
    onCancel: () => void;
    isSubmitting: boolean;
    error: string | null;
}

export default function EditPickupAddressForm({
    formData,
    onFormDataChange,
    onSubmit,
    onCancel,
    isSubmitting,
    error
}: EditPickupAddressFormProps) {
    return (
        <div className="mb-6 p-4 border border-blue-200 rounded-lg bg-blue-50">
            <div className="flex justify-between items-center mb-4">
                <h3 className="text-lg font-medium">Edit Pickup Address</h3>
                <button
                    onClick={onCancel}
                    className="text-gray-600 hover:text-gray-800"
                >
                    ✕ Cancel
                </button>
            </div>
            {error && (
                <div className="mb-4 p-3 bg-red-50 border border-red-200 rounded text-red-700">
                    {error}
                </div>
            )}
            <form onSubmit={onSubmit} className="grid grid-cols-2 gap-4">
                <div>
                    <label className="block text-sm font-medium mb-1">Pickup Location Name *</label>
                    <input
                        type="text"
                        required
                        value={formData.name}
                        onChange={(e) => onFormDataChange({...formData, name: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Contact Name *</label>
                    <input
                        type="text"
                        required
                        value={formData.contact_name}
                        onChange={(e) => onFormDataChange({...formData, contact_name: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Phone *</label>
                    <input
                        type="tel"
                        required
                        value={formData.phone}
                        onChange={(e) => onFormDataChange({...formData, phone: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Email</label>
                    <input
                        type="email"
                        value={formData.email}
                        onChange={(e) => onFormDataChange({...formData, email: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div className="col-span-2">
                    <label className="block text-sm font-medium mb-1">Address Line 1 *</label>
                    <input
                        type="text"
                        required
                        value={formData.address}
                        onChange={(e) => onFormDataChange({...formData, address: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div className="col-span-2">
                    <label className="block text-sm font-medium mb-1">Address Line 2</label>
                    <input
                        type="text"
                        value={formData.address_2}
                        onChange={(e) => onFormDataChange({...formData, address_2: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">City *</label>
                    <input
                        type="text"
                        required
                        value={formData.city}
                        onChange={(e) => onFormDataChange({...formData, city: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">State *</label>
                    <input
                        type="text"
                        required
                        value={formData.state}
                        onChange={(e) => onFormDataChange({...formData, state: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Pincode *</label>
                    <input
                        type="text"
                        required
                        value={formData.pincode}
                        onChange={(e) => onFormDataChange({...formData, pincode: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Country *</label>
                    <input
                        type="text"
                        required
                        value={formData.country}
                        onChange={(e) => onFormDataChange({...formData, country: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">Company Name</label>
                    <input
                        type="text"
                        value={formData.company_name}
                        onChange={(e) => onFormDataChange({...formData, company_name: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div>
                    <label className="block text-sm font-medium mb-1">GSTIN</label>
                    <input
                        type="text"
                        value={formData.gstin}
                        onChange={(e) => onFormDataChange({...formData, gstin: e.target.value})}
                        className="w-full px-3 py-2 border border-gray-300 rounded"
                    />
                </div>
                <div className="col-span-2">
                    <button
                        type="submit"
                        disabled={isSubmitting}
                        className="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                    >
                        {isSubmitting ? 'Updating...' : 'Update Pickup Address'}
                    </button>
                </div>
            </form>
        </div>
    );
}
