<?php

namespace Susheelbhai\Laraship\Console\Modifiers;

use Illuminate\Support\Facades\File;

class ControllerModifier
{
    public static function modifyAdminOrderController(): void
    {
        $path = app_path('Http/Controllers/Admin/OrderController.php');
        if (! File::exists($path)) {
            throw new \Exception('Admin OrderController not found');
        }

        $content = File::get($path);

        // Check if already modified
        if (str_contains($content, "'shipment'") && str_contains($content, "shipment' => \$order->shipment")) {
            throw new \Exception('Already integrated');
        }

        // Add 'shipment' to load() method
        $content = preg_replace(
            "/(\\\$order->load\(\[.*?)(\]\);)/s",
            "$1, 'shipment'$2",
            $content
        );

        // Add shipment to $orderData array - insert before the closing ];
        $shipmentCode = "        'shipment' => \$order->shipment ? [\n".
            "            'id' => \$order->shipment->id,\n".
            "            'tracking_number' => \$order->shipment->tracking_number,\n".
            "            'awb_code' => \$order->shipment->awb_code,\n".
            "            'shipping_provider' => \$order->shipment->shipping_provider,\n".
            "            'status' => \$order->shipment->status,\n".
            "            'created_at' => \$order->shipment->created_at,\n".
            "        ] : null,\n";

        // Find the line with return $this->render and insert shipment before the closing ];
        $content = preg_replace(
            "/(.*\\\$orderData\s*=\s*\[.*?)(    \];\s*return\s+\\\$this->render)/s",
            "$1{$shipmentCode}$2",
            $content
        );

        File::put($path, $content);
    }

    public static function modifyUserOrderController(): void
    {
        $path = app_path('Http/Controllers/User/OrderController.php');
        if (! File::exists($path)) {
            throw new \Exception('User OrderController not found');
        }

        $content = File::get($path);

        // Check if already modified
        if (str_contains($content, "'shipment'")) {
            throw new \Exception('Already integrated');
        }

        // Add 'shipment' to load() method
        $content = preg_replace(
            "/(\\\$order->load\(\[.*?)(\]\);)/s",
            "$1, 'shipment'$2",
            $content
        );

        File::put($path, $content);
    }
}
