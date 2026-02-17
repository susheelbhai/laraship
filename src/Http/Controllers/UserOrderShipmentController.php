<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Susheelbhai\Laraship\Facades\Laraship;

class OrderShipmentController extends Controller
{
    /**
     * Get tracking information for user's order
     */
    public function trackShipment($orderId)
    {
        try {
            $orderModel = config('laraship.order_model');
            $order = $orderModel::findOrFail($orderId);

            // Ensure the order belongs to the authenticated user
            if ($order->user_id !== Auth::id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access',
                ], 403);
            }

            if (! $order->hasShipment()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No shipment found for this order',
                ], 404);
            }

            $trackingData = Laraship::getTrackingInfo($order->shipment->tracking_number);

            // Extract tracking info from provider
            $providerTracking = $trackingData['tracking_info'] ?? [];
            $shipment = $trackingData['shipment'];

            // Format response for frontend - use only database data
            $tracking = [
                'tracking_number' => $shipment->tracking_number,
                'status' => $shipment->status, // Use actual shipment status from database
                'current_location' => $providerTracking['location'] ?? 'N/A',
                'estimated_delivery' => $providerTracking['estimated_delivery'] ?? 'N/A',
                'history' => [],
            ];

            // Use only database status history - never use provider mock data
            if (isset($trackingData['status_history']) && $trackingData['status_history']->isNotEmpty()) {
                $tracking['history'] = $trackingData['status_history']->map(function ($history) {
                    return [
                        'status' => $history->status,
                        'location' => $history->location ?? '',
                        'timestamp' => $history->occurred_at?->toIso8601String() ?? '',
                        'description' => $history->description ?? '',
                    ];
                })->toArray();
            }

            return response()->json([
                'success' => true,
                'tracking' => $tracking,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get tracking info: '.$e->getMessage(),
            ], 500);
        }
    }
}
