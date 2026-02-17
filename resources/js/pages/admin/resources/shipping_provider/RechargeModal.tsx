import { useState } from 'react';
import axios from 'axios';

interface RechargeModalProps {
    isOpen: boolean;
    onClose: () => void;
    providerId: number;
    currency?: string;
    rechargeUrl?: string | null;
}

export default function RechargeModal({
    isOpen,
    onClose,
    providerId,
    currency = 'INR',
    rechargeUrl,
}: RechargeModalProps) {
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
                route('admin.shipping_provider.recharge', providerId),
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
            const errorMessage = err.response?.data?.message || 'Failed to recharge wallet';
            const statusCode = err.response?.status;

            // If provider doesn't support recharge (404) and has a recharge URL, redirect
            if ((statusCode === 404 || errorMessage.includes('does not support')) && rechargeUrl) {
                window.open(rechargeUrl, '_blank');
                handleClose();
            } else {
                setError(errorMessage);
            }
        } finally {
            setProcessing(false);
        }
    };

    const handleClose = () => {
        setRechargeAmount('');
        setError(null);
        setRechargeResult(null);
        onClose();
    };

    if (!isOpen) return null;

    return (
        <div className="fixed inset-0 bg-black/70 bg-opacity-50 flex items-center justify-center z-50">
            <div className="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                <h3 className="text-lg font-semibold mb-4">Recharge Wallet</h3>

                {!rechargeResult ? (
                    <form onSubmit={handleRecharge}>
                        <div className="mb-4">
                            <label className="block text-sm font-medium text-gray-700 mb-2">
                                Amount ({currency})
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
                                onClick={handleClose}
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
                                    <dd>
                                        {currency} {rechargeResult.amount}
                                    </dd>
                                </div>
                                <div className="flex justify-between">
                                    <dt className="font-medium">Status:</dt>
                                    <dd className="capitalize">{rechargeResult.status}</dd>
                                </div>
                            </dl>
                        </div>
                        <p className="text-sm text-gray-600 text-center">Redirecting...</p>
                    </div>
                )}
            </div>
        </div>
    );
}
