import { useState, useEffect } from 'react';
import TableCard from '@/components/table/table-card';
import axios from 'axios';
import Swal from 'sweetalert2';
import AddPickupAddressForm from './AddPickupAddressForm';
import EditPickupAddressForm from './EditPickupAddressForm';
import PickupAddressesTable from './PickupAddressesTable';

interface PickupAddressesSectionProps {
    providerId: number;
}

export default function PickupAddressesSection({ providerId }: PickupAddressesSectionProps) {
    const [pickupAddresses, setPickupAddresses] = useState<any[]>([]);
    const [availableAddresses, setAvailableAddresses] = useState<any[]>([]);
    const [loadingAddresses, setLoadingAddresses] = useState(false);
    const [addressError, setAddressError] = useState<string | null>(null);
    const [showAddForm, setShowAddForm] = useState(false);
    const [addingAddress, setAddingAddress] = useState(false);
    const [addError, setAddError] = useState<string | null>(null);
    const [selectedAddressId, setSelectedAddressId] = useState<string>('');
    const [editingAddress, setEditingAddress] = useState<any>(null);
    const [updatingAddress, setUpdatingAddress] = useState(false);
    const [updateError, setUpdateError] = useState<string | null>(null);
    const [deletingAddressId, setDeletingAddressId] = useState<string | null>(null);
    const [formData, setFormData] = useState({
        name: '',
        contact_name: '',
        phone: '',
        email: '',
        address: '',
        address_2: '',
        city: '',
        state: '',
        pincode: '',
        country: 'India',
        company_name: '',
        gstin: '',
    });

    const fetchPickupAddresses = async () => {
        setLoadingAddresses(true);
        setAddressError(null);
        try {
            const response = await axios.get(
                route('admin.shipping_provider.fetch_pickup_addresses', providerId)
            );
            
            if (response.data.success) {
                setPickupAddresses(response.data.data || []);
            } else {
                setAddressError(response.data.message || 'Failed to fetch addresses');
            }
        } catch (error: any) {
            if (error.response?.data?.message) {
                setAddressError(error.response.data.message);
            } else {
                setAddressError('Failed to fetch pickup addresses from provider');
            }
            console.error('Failed to fetch pickup addresses:', error);
        } finally {
            setLoadingAddresses(false);
        }
    };

    const fetchAvailableAddresses = async () => {
        try {
            const response = await axios.get(
                route('admin.shipping_provider.available_pickup_addresses', providerId)
            );
            
            if (response.data.success) {
                setAvailableAddresses(response.data.data || []);
            }
        } catch (error: any) {
            console.error('Failed to fetch available addresses:', error);
        }
    };

    const handleAddAddress = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedAddressId) return;
        
        setAddingAddress(true);
        setAddError(null);
        
        try {
            const response = await axios.post(
                route('admin.shipping_provider.create_pickup_address', providerId),
                { pickup_address_id: selectedAddressId }
            );
            
            if (response.data.success) {
                setSelectedAddressId('');
                setShowAddForm(false);
                fetchPickupAddresses();
                fetchAvailableAddresses();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Pickup address added successfully!',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                setAddError(response.data.message || 'Failed to add address');
            }
        } catch (error: any) {
            if (error.response?.data?.message) {
                setAddError(error.response.data.message);
            } else {
                setAddError('Failed to add pickup address');
            }
            console.error('Failed to add pickup address:', error);
        } finally {
            setAddingAddress(false);
        }
    };

    const handleEditClick = (address: any) => {
        setEditingAddress(address);
        setFormData({
            name: address.name || '',
            contact_name: address.contact_name || address.name || '',
            phone: address.phone || '',
            email: address.email || '',
            address: address.address || address.address_line1 || '',
            address_2: address.address_2 || address.address_line2 || '',
            city: address.city || '',
            state: address.state || '',
            pincode: address.pincode || address.pin_code || '',
            country: address.country || 'India',
            company_name: address.company_name || '',
            gstin: address.gstin || '',
        });
        setShowAddForm(false);
    };

    const handleUpdateAddress = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingAddress) return;
        
        setUpdatingAddress(true);
        setUpdateError(null);
        
        try {
            const response = await axios.put(
                route('admin.shipping_provider.update_pickup_address', {
                    provider: providerId,
                    addressId: editingAddress.id
                }),
                formData
            );
            
            if (response.data.success) {
                setEditingAddress(null);
                setFormData({
                    name: '',
                    contact_name: '',
                    phone: '',
                    email: '',
                    address: '',
                    address_2: '',
                    city: '',
                    state: '',
                    pincode: '',
                    country: 'India',
                    company_name: '',
                    gstin: '',
                });
                fetchPickupAddresses();
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Pickup address updated successfully!',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                setUpdateError(response.data.message || 'Failed to update address');
            }
        } catch (error: any) {
            if (error.response?.data?.message) {
                setUpdateError(error.response.data.message);
            } else {
                setUpdateError('Failed to update pickup address');
            }
            console.error('Failed to update pickup address:', error);
        } finally {
            setUpdatingAddress(false);
        }
    };

    const handleDeleteAddress = async (addressId: string) => {
        const result = await Swal.fire({
            title: 'Are you sure?',
            text: 'This will remove the address from the provider\'s system.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Yes, delete it!',
            cancelButtonText: 'Cancel'
        });

        if (!result.isConfirmed) {
            return;
        }
        
        setDeletingAddressId(addressId);
        
        try {
            const response = await axios.delete(
                route('admin.shipping_provider.delete_pickup_address', {
                    provider: providerId,
                    addressId: addressId
                })
            );
            
            if (response.data.success) {
                fetchPickupAddresses();
                fetchAvailableAddresses();
                Swal.fire({
                    icon: 'success',
                    title: 'Deleted!',
                    text: 'Pickup address has been deleted.',
                    timer: 2000,
                    showConfirmButton: false
                });
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: response.data.message || 'Failed to delete address'
                });
            }
        } catch (error: any) {
            const errorMessage = error.response?.data?.message || 'Failed to delete pickup address';
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: errorMessage
            });
            console.error('Failed to delete pickup address:', error);
        } finally {
            setDeletingAddressId(null);
        }
    };

    const handleCancelEdit = () => {
        setEditingAddress(null);
        setFormData({
            name: '',
            contact_name: '',
            phone: '',
            email: '',
            address: '',
            address_2: '',
            city: '',
            state: '',
            pincode: '',
            country: 'India',
            company_name: '',
            gstin: '',
        });
        setUpdateError(null);
    };

    useEffect(() => {
        fetchPickupAddresses();
        fetchAvailableAddresses();
    }, [providerId]);

    return (
        <div className="mt-6">
            <TableCard>
                <div className="p-6">
                    <div className="flex justify-between items-center mb-4">
                        <h2 className="text-xl font-semibold">Pickup Addresses from Provider API</h2>
                        <div className="flex gap-2">
                            <button
                                onClick={() => setShowAddForm(!showAddForm)}
                                className="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                            >
                                {showAddForm ? 'Cancel' : 'Add New'}
                            </button>
                            <button
                                onClick={() => {
                                    fetchPickupAddresses();
                                    fetchAvailableAddresses();
                                }}
                                disabled={loadingAddresses}
                                className="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 disabled:opacity-50"
                            >
                                {loadingAddresses ? 'Loading...' : 'Refresh'}
                            </button>
                        </div>
                    </div>

                    {showAddForm && (
                        <AddPickupAddressForm
                            availableAddresses={availableAddresses}
                            selectedAddressId={selectedAddressId}
                            onSelectedAddressChange={setSelectedAddressId}
                            onSubmit={handleAddAddress}
                            isSubmitting={addingAddress}
                            error={addError}
                        />
                    )}

                    {editingAddress && (
                        <EditPickupAddressForm
                            formData={formData}
                            onFormDataChange={setFormData}
                            onSubmit={handleUpdateAddress}
                            onCancel={handleCancelEdit}
                            isSubmitting={updatingAddress}
                            error={updateError}
                        />
                    )}

                    {loadingAddresses ? (
                        <div className="text-center py-8 text-gray-500">
                            Loading addresses from provider...
                        </div>
                    ) : addressError ? (
                        <div className="text-center py-8">
                            <p className="text-red-600 mb-2">{addressError}</p>
                            <p className="text-sm text-gray-500">
                                This provider may not support fetching pickup addresses via API.
                            </p>
                        </div>
                    ) : pickupAddresses.length === 0 ? (
                        <div className="text-center py-8 text-gray-500">
                            No pickup addresses found from provider.
                        </div>
                    ) : (
                        <PickupAddressesTable
                            addresses={pickupAddresses}
                            onEdit={handleEditClick}
                            onDelete={handleDeleteAddress}
                            deletingAddressId={deletingAddressId}
                        />
                    )}
                </div>
            </TableCard>
        </div>
    );
}
