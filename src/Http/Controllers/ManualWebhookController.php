<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ManualWebhookRequest;
use Illuminate\Http\Request;
use Susheelbhai\Laraship\Models\Shipment;

class ManualWebhookController extends Controller
{
    /**
     * Show the manual webhook form.
     */
    public function create(Request $request)
    {
        // Get all shipments with mock provider for dropdown
        $shipments = Shipment::whereHas('provider', function ($query) {
            $query->where('adapter_class', 'like', '%MockAdapter%');
        })
            ->with('provider', 'order')
            ->latest()
            ->get()
            ->map(function ($shipment) {
                return [
                    'id' => $shipment->id,
                    'tracking_number' => $shipment->tracking_number,
                    'order_id' => $shipment->order_id,
                    'current_status' => $shipment->status,
                    'provider_name' => $shipment->provider->display_name,
                ];
            });

        $statusOptions = [
            ['value' => 'pending', 'name' => 'Pending'],
            ['value' => 'booked', 'name' => 'Booked'],
            ['value' => 'picked_up', 'name' => 'Picked Up'],
            ['value' => 'in_transit', 'name' => 'In Transit'],
            ['value' => 'out_for_delivery', 'name' => 'Out for Delivery'],
            ['value' => 'delivered', 'name' => 'Delivered'],
            ['value' => 'failed', 'name' => 'Failed'],
            ['value' => 'returned', 'name' => 'Returned'],
            ['value' => 'cancelled', 'name' => 'Cancelled'],
        ];

        return $this->render('admin/resources/manual_webhook/create', compact('shipments', 'statusOptions'));
    }

    /**
     * Send manual webhook.
     */
    public function send(ManualWebhookRequest $request)
    {
        try {
            // Verify shipment exists and uses mock provider
            $shipment = Shipment::where('tracking_number', $request->tracking_number)
                ->whereHas('provider', function ($query) {
                    $query->where('adapter_class', 'like', '%MockAdapter%');
                })
                ->with('provider')
                ->first();

            if (! $shipment) {
                return back()
                    ->withErrors(['tracking_number' => 'Shipment not found or not using Mock provider'])
                    ->withInput();
            }

            // Prepare webhook payload
            $payload = [
                'tracking_number' => $request->tracking_number,
                'status' => $request->status,
                'location' => $request->location ?? 'Test Location',
                'description' => $request->description ?? 'Manual webhook test',
                'occurred_at' => $request->occurred_at ?? now()->toIso8601String(),
                'provider' => 'mock',
            ];

            // Generate signature (mock provider signature)
            // Handle both encrypted credentials and null credentials
            $credentials = $shipment->provider->credentials;
            $secret = is_array($credentials) && isset($credentials['api_secret'])
                ? $credentials['api_secret']
                : 'mock_secret';
            $signature = hash_hmac('sha256', json_encode($payload), $secret);

            // Get webhook URL - use the provider name from the shipment
            $providerName = $shipment->provider->name;

            // Instead of making HTTP request (which causes deadlock),
            // directly process the webhook
            $webhookRequest = Request::create(
                '/webhooks/shipping/'.$providerName,
                'POST',
                $payload,
                [],
                [],
                ['CONTENT_TYPE' => 'application/json'],
                json_encode($payload)
            );
            $webhookRequest->headers->set('X-Signature', $signature);
            $webhookRequest->headers->set('X-Webhook-Signature', $signature);

            // Get the shipping manager and process webhook directly
            $shippingManager = app(\Susheelbhai\Laraship\Services\ShippingManager::class);

            try {
                $shippingManager->processWebhook($providerName, $webhookRequest);

                return redirect()
                    ->route('admin.manual_webhook.create')
                    ->with('success', 'Webhook sent successfully! Status updated to: '.$request->status);
            } catch (\Exception $e) {
                return back()
                    ->withErrors(['webhook' => 'Webhook processing failed: '.$e->getMessage()])
                    ->withInput();
            }

        } catch (\Exception $e) {
            return back()
                ->withErrors(['webhook' => 'Error sending webhook: '.$e->getMessage()])
                ->withInput();
        }
    }

    /**
     * Render method to support both Inertia and Blade.
     */
    protected function render(string $view, array $data = [], $render_type = null)
    {
        $renderType = $render_type ?? config('app.render_type', 'inertia');

        if ($renderType === 'inertia') {
            return inertia($view, $data);
        }

        return view($view, $data);
    }
}
