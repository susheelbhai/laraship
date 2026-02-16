<?php

namespace Susheelbhai\Laraship\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Susheelbhai\Laraship\Exceptions\InvalidWebhookSignatureException;
use Susheelbhai\Laraship\Exceptions\ShipmentNotFoundException;
use Susheelbhai\Laraship\Services\ShippingManager;

class WebhookController extends Controller
{
    public function __construct(
        private ShippingManager $shippingManager
    ) {}

    /**
     * Handle incoming webhook from shipping provider.
     */
    public function handle(Request $request, string $provider): \Illuminate\Http\JsonResponse
    {
        try {
            // Process the webhook
            $this->shippingManager->processWebhook($provider, $request);

            Log::info('Webhook processed successfully', [
                'provider' => $provider,
                'tracking_number' => $request->input('tracking_number'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
            ]);

        } catch (InvalidWebhookSignatureException $e) {
            Log::warning('Invalid webhook signature', [
                'provider' => $provider,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Invalid signature',
            ], 401);

        } catch (ShipmentNotFoundException $e) {
            Log::warning('Shipment not found for webhook', [
                'provider' => $provider,
                'tracking_number' => $request->input('tracking_number'),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Shipment not found',
            ], 404);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'provider' => $provider,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
