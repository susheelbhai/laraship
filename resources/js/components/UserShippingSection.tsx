import { useState } from 'react';
import { Package, MapPin, Loader2 } from 'lucide-react';
import Table from '@/components/table/table';
import TableCard from '@/components/table/table-card';
import TBody from '@/components/table/tbody';

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

export default function UserShippingSection({ orderId, shipment }: Props) {
    const [tracking, setTracking] = useState<TrackingInfo | null>(null);
    const [loading, setLoading] = useState(false);
    const [showTracking, setShowTracking] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const fetchTracking = async () => {
        setLoading(true);
        setError(null);
        try {
            const response = await fetch(`/order/${orderId}/shipping/track`, {
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

    if (!shipment) {
        return (
            <TableCard className="mt-6">
                <div className="p-4 border-b border-gray-200">
                    <div className="flex items-center gap-2">
                        <Package className="h-5 w-5 text-primary" />
                        <h3 className="text-lg font-semibold">Shipping Information</h3>
                    </div>
                </div>
                <div className="p-6">
                    <div className="text-center py-8">
                        <Package className="h-12 w-12 text-gray-400 mx-auto mb-4" />
                        <p className="text-gray-600">Your order has not been shipped yet</p>
                        <p className="text-sm text-gray-500 mt-2">
                            We'll notify you once your order is shipped
                        </p>
                    </div>
                </div>
            </TableCard>
        );
    }

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
                            <td className="p-3 capitalize">
                                {shipment.shipping_provider?.replace('-', ' ') || 'N/A'}
                            </td>
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

                <div className="mt-4">
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
                </div>

                {showTracking && tracking && (
                    <div className="mt-6 border-t pt-6">
                        <h4 className="font-semibold mb-3">Tracking Information</h4>
                        <div className="bg-gray-50 p-4 rounded mb-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-gray-600">Current Status</p>
                                    <p className="font-semibold capitalize">
                                        {tracking.status?.replace('_', ' ') || 'N/A'}
                                    </p>
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
                                                <p className="font-medium capitalize">
                                                    {event.status?.replace('_', ' ') || 'N/A'}
                                                </p>
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
        </TableCard>
    );
}
