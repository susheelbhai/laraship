<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Susheelbhai\Laraship\Facades\Laraship;

class OrderShipmentController extends Controller
{
    /**
     * Show shipping rates for an order
     */
    public function getRates($orderId)
    {
        try {
            $orderModel = config('laraship.order_model');
            $order = $orderModel::with(['address', 'items.product'])->findOrFail($orderId);

            // Convert model address to DTO
            $addressDto = \Susheelbhai\Laraship\DTOs\Address::fromModel($order->address);

            // Use the facade with order and address DTO
            $rates = Laraship::calculateRates($order, $addressDto);

            // Get all providers to map names to IDs
            $providers = \Susheelbhai\Laraship\Models\ShippingProvider::enabled()->get()->keyBy('name');

            // Format rates for response
            $formattedRates = $rates->map(function ($rate) use ($providers) {
                // Try to find provider by name
                $provider = $providers->first(function ($p) use ($rate) {
                    return stripos($rate->providerName, $p->display_name) !== false
                        || stripos($p->display_name, $rate->providerName) !== false;
                });

                return [
                    'provider_id' => $provider?->id ?? 1,
                    'provider_name' => $rate->providerName,
                    'service_type' => $rate->serviceType,
                    'amount' => $rate->amount,
                    'estimated_days' => $rate->estimatedDays,
                    'formatted_amount' => $rate->getFormattedAmount(),
                ];
            });

            return response()->json([
                'success' => true,
                'rates' => $formattedRates,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to calculate shipping rates: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Book shipment for an order
     */
    public function bookShipment(Request $request, $orderId)
    {
        $request->validate([
            'provider_id' => 'required|exists:shipping_providers,id',
            'service_type' => 'required|string',
        ]);

        try {
            $orderModel = config('laraship.order_model');
            $order = $orderModel::with(['address', 'items.product'])->findOrFail($orderId);

            // Get the selected provider
            $provider = \Susheelbhai\Laraship\Models\ShippingProvider::findOrFail($request->provider_id);

            // Use the facade to book courier with specific provider
            $booking = Laraship::bookCourierWithProvider($order, $provider->name, $request->service_type);

            return redirect()->back()->with('success', 'Shipment booked successfully! Tracking: '.$booking->trackingNumber);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to book shipment: '.$e->getMessage());
        }
    }

    /**
     * Get tracking information
     */
    public function trackShipment($orderId)
    {
        try {
            $orderModel = config('laraship.order_model');
            $order = $orderModel::findOrFail($orderId);

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

    /**
     * Cancel shipment
     */
    public function cancelShipment($orderId)
    {
        try {
            $orderModel = config('laraship.order_model');
            $order = $orderModel::findOrFail($orderId);

            if (! $order->hasShipment()) {
                return redirect()->back()->with('error', 'No shipment found for this order');
            }

            $shipment = $order->shipment;
            $shipment->delete();
            $order->update(['status' => 'processing']);

            return redirect()->back()->with('success', 'Shipment cancelled successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to cancel shipment: '.$e->getMessage());
        }
    }
}
