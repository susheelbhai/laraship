import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Package, Truck, MapPin, X, Loader2 } from 'lucide-react';
import Table from '@/components/table/table';
import TableCard from '@/components/table/table-card';
import TBody from '@/components/table/tbody';
import THead from '@/components/table/thead';
import Button from '@/components/button';

type ShippingRate = {
    provider_id: number;
    provider_name: string;
    service_type: string;
    amount: number;
    estimated_days: number;
    formatted_amount: string;
};

type Shipment = {
    id: number;
    tracking_number: string;
    awb_code: string;
    shipping_provider: string;
    status: string;
    created_at: string;
};

type TrackingHistory = {
    status: string;
    location: string;
    timestamp: string;
    description: string;
};

type TrackingInfo = {
    tracking_number: string;
    status: string;
    current_location: string;
    estimated_delivery: string;
    history: TrackingHistory[];
};

type Props = {
    orderId: number;
    shipment?: Shipment;
};

export default function ShippingSection({ orderId, shipment }: Props) {
    const [rates, setRates] = useState<ShippingRate[]>([]);
    const [tracking, setTracking] = useState<TrackingInfo | null>(null);
    const [loading, setLoading] = useState(false);
    const [showRates, setShowRates] = useState(false);
    const [showTracking, setShowTracking] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const fetchRates = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`/admin/order/${orderId}/shipping/rates`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            if (data.success) {
                setRates(data.rates);
                setShowRates(true);
            } else {
                setError(data.message || 'Failed to fetch rates');
            }
        } catch (err) {
            setError('Failed to fetch shipping rates');
        } finally {
            setLoading(false);
        }
    };

    const bookShipment = async (providerId: number, serviceType: string) => {
        setLoading(true);
        router.post(`/admin/order/${orderId}/shipping/book`, {
            provider_id: providerId,
            service_type: serviceType,
        }, {
            onFinish: () => setLoading(false),
        });
    };

    const fetchTracking = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`/admin/order/${orderId}/shipping/track`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });
            const data = await response.json();
            if (data.success) {
                setTracking(data.tracking);
                setShowTracking(true);
            } else {
                setError(data.message || 'Failed to fetch tracking info');
            }
        } catch (err) {
            setError('Failed to fetch tracking information');
        } finally {
            setLoading(false);
        }
    };

    const getStatusColor = (status: string) => {
        const colors: { [key: string]: string } = {
            pending: 'bg-secondary/20 text-secondary',
            booked: 'bg-primary/20 text-primary',
            in_transit: 'bg-primary/20 text-primary',
            out_for_delivery: 'bg-accent/20 text-accent',
            delivered: 'bg-accent/20 text-accent',
            cancelled: 'bg-destructive/20 text-destructive',
            failed: 'bg-destructive/20 text-destructive',
        };
        return colors[status] || 'bg-muted text-muted-foreground';
    };

    return (
        <TableCard className="mt-6">
            <div className="p-4 border-b border-gray-200">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-2">
                        <Package className="h-5 w-5 text-primary" />
                        <h3 className="text-lg font-semibold">Shipping Information</h3>
                    </div>
                </div>
            </div>

            {error && (
                <div className="p-4 bg-destructive/10 border-l-4 border-destructive text-destructive">
                    {error}
                </div>
            )}

            {!shipment ? (
                <div className="p-6">
                    <div className="text-center py-8">
                        <Truck className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-600 mb-4">No shipment booked for this order</p>
                        <button
                            onClick={fetchRates}
                            disabled={loading}
                            className="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 disabled:opacity-50 flex items-center gap-2 mx-auto"
                        >
                            {loading ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Loading Rates...
                                </>
                            ) : (
                                <>
                                    <Package className="h-4 w-4" />
                                    Get Shipping Rates
                                </>
                            )}
                        </button>
                    </div>

                    {showRates && rates.length > 0 && (
                        <div className="mt-6">
                            <h4 className="font-semibold mb-3">Available Shipping Rates</h4>
                            <Table>
                                <THead
                                    data={[
                                        { title: 'Provider', className: 'p-3' },
                                        { title: 'Service', className: 'p-3' },
                                        { title: 'Rate', className: 'p-3' },
                                        { title: 'Delivery', className: 'p-3' },
                                        { title: 'Action', className: 'p-3' },
                                    ]}
                                />
                                <TBody>
                                    {rates.map((rate, index) => (
                                        <tr key={index} className="border-t border-gray-200">
                                            <td className="p-3">{rate.provider_name}</td>
                                            <td className="p-3 capitalize">{rate.service_type}</td>
                                            <td className="p-3 font-semibold">{rate.formatted_amount}</td>
                                            <td className="p-3">{rate.estimated_days} days</td>
                                            <td className="p-3">
                                                <button
                                                    onClick={() => bookShipment(rate.provider_id, rate.service_type)}
                                                    disabled={loading}
                                                    className="px-3 py-1 bg-accent text-white text-sm rounded hover:bg-accent/90 disabled:opacity-50"
                                                >
                                                    {loading ? 'Booking...' : 'Book'}
                                                </button>
                                            </td>
                                        </tr>
                                    ))}
                                </TBody>
                            </Table>
                        </div>
                    )}
                </div>
            ) : (
                <div className="p-6">
                    <Table>
                        <TBody>
                            <tr className="border-y border-gray-200">
                                <td className="p-3 font-semibold">Tracking Number</td>
                                <td className="p-3 font-mono">{shipment.tracking_number}</td>
                            </tr>
                            <tr className="border-y border-gray-200">
                                <td className="p-3 font-semibold">AWB Code</td>
                                <td className="p-3 font-mono">{shipment.awb_code}</td>
                            </tr>
                            <tr className="border-y border-gray-200">
                                <td className="p-3 font-semibold">Shipping Provider</td>
                                <td className="p-3 capitalize">{shipment.shipping_provider?.replace('-', ' ') || 'N/A'}</td>
                            </tr>
                            <tr className="border-y border-gray-200">
                                <td className="p-3 font-semibold">Status</td>
                                <td className="p-3">
                                    <span
                                        className={`text-xs px-3 py-1 rounded-full font-semibold ${getStatusColor(
                                            shipment.status
                                        )}`}
                                    >
                                        {shipment.status.toUpperCase().replace('_', ' ')}
                                    </span>
                                </td>
                            </tr>
                        </TBody>
                    </Table>

                    <div className="mt-4 flex gap-3">
                        <button
                            onClick={fetchTracking}
                            disabled={loading}
                            className="px-4 py-2 bg-primary text-white rounded hover:bg-primary/90 disabled:opacity-50 flex items-center gap-2"
                        >
                            {loading ? (
                                <>
                                    <Loader2 className="h-4 w-4 animate-spin" />
                                    Loading...
                                </>
                            ) : (
                                <>
                                    <MapPin className="h-4 w-4" />
                                    Track Shipment
                                </>
                            )}
                        </button>
                        <Button
                            href={`/admin/order/${orderId}/shipping/cancel`}
                            method="delete"
                            className="flex items-center gap-2"
                        >
                            <X className="h-4 w-4" />
                            Cancel Shipment
                        </Button>
                    </div>

                    {showTracking && tracking && (
                        <div className="mt-6 border-t pt-6">
                            <h4 className="font-semibold mb-3">Tracking Information</h4>
                            <div className="bg-gray-50 p-4 rounded mb-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-sm text-gray-600">Current Status</p>
                                        <p className="font-semibold capitalize">{tracking.status?.replace('_', ' ') || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Current Location</p>
                                        <p className="font-semibold">{tracking.current_location || 'N/A'}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Estimated Delivery</p>
                                        <p className="font-semibold">{tracking.estimated_delivery || 'N/A'}</p>
                                    </div>
                                </div>
                            </div>

                            {tracking.history && tracking.history.length > 0 && (
                                <div>
                                    <h5 className="font-semibold mb-2">Tracking History</h5>
                                    <div className="space-y-3">
                                        {tracking.history.map((event, index) => (
                                            <div key={index} className="flex gap-3 border-l-2 border-primary pl-4">
                                                <div className="flex-1">
                                                    <p className="font-medium capitalize">{event.status?.replace('_', ' ') || 'N/A'}</p>
                                                    <p className="text-sm text-gray-600">{event.description || ''}</p>
                                                    <p className="text-sm text-gray-500">{event.location || ''}</p>
                                                    <p className="text-xs text-gray-400">{event.timestamp || ''}</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            )}
                        </div>
                    )}
                </div>
            )}
        </TableCard>
    );
}
