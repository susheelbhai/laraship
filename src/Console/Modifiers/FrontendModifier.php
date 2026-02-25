<?php

namespace Susheelbhai\Laraship\Console\Modifiers;

use Illuminate\Support\Facades\File;

class FrontendModifier
{
    public static function modifyAdminOrderShowPage(): void
    {
        $path = resource_path('js/pages/admin/resources/order/show.tsx');
        if (! File::exists($path)) {
            throw new \Exception('Admin order show page not found');
        }

        $content = File::get($path);

        // Check if already added
        if (str_contains($content, 'ShippingSection')) {
            throw new \Exception('Already integrated');
        }

        // Add import
        $importStatement = "import ShippingSection from '@/components/shipping/ShippingSection';";
        if (! str_contains($content, $importStatement)) {
            $content = preg_replace(
                "/(import.*?from '@inertiajs\/react';)/",
                "$1\n{$importStatement}",
                $content
            );
        }

        // Add shipment to Order type - insert before the closing };
        $shipmentType = "    shipment?: {\n".
            "        id: number;\n".
            "        tracking_number: string;\n".
            "        awb_code: string;\n".
            "        shipping_provider: string;\n".
            "        status: string;\n".
            "        created_at: string;\n".
            "    };\n";

        // Find "payments: Payment[];" and add shipment after it
        $content = preg_replace(
            "/(payments:\s*Payment\[\];)/",
            "$1\n{$shipmentType}",
            $content
        );

        File::put($path, $content);
    }

    public static function modifyUserOrderShowPage(): void
    {
        $path = resource_path('js/pages/user/orders/show.tsx');
        if (! File::exists($path)) {
            throw new \Exception('User order show page not found');
        }

        $content = File::get($path);

        // Check if already added
        if (str_contains($content, 'UserShippingSection')) {
            throw new \Exception('Already integrated');
        }

        // Add import
        $importStatement = "import UserShippingSection from '@/components/shipping/UserShippingSection';";
        if (! str_contains($content, $importStatement)) {
            $content = preg_replace(
                "/(import.*?from '@\/layouts\/user\/app-layout';)/",
                "$1\n{$importStatement}",
                $content
            );
        }

        // Add shipment to Order interface - insert before the closing }
        $shipmentType = "    shipment?: {\n".
            "        id: number;\n".
            "        tracking_number: string;\n".
            "        awb_code: string;\n".
            "        shipping_provider: string;\n".
            "        status: string;\n".
            "        created_at: string;\n".
            "    };\n";

        // Find "address: Address;" and add shipment after it
        $content = preg_replace(
            "/(address:\s*Address;)/",
            "$1\n{$shipmentType}",
            $content
        );

        File::put($path, $content);
    }

    public static function modifyAdminSidebar(): void
    {
        $path = resource_path('data/js/sidebar_admin.ts');
        if (! File::exists($path)) {
            throw new \Exception('Admin sidebar file not found');
        }

        $content = File::get($path);

        // Check if Package and Webhook are in the IMPORT section (first 35 lines)
        $lines = explode("\n", $content);
        $importSection = implode("\n", array_slice($lines, 0, 35));

        $hasPackageImport = str_contains($importSection, 'Package');
        $hasWebhookImport = str_contains($importSection, 'Webhook');
        $hasWarehouseImport = str_contains($importSection, 'Warehouse');
        $hasMenu = str_contains($content, 'admin.shipping_provider.index');

        // If everything is already there, skip
        if ($hasPackageImport && $hasWebhookImport && $hasWarehouseImport && $hasMenu) {
            throw new \Exception('Already integrated');
        }

        $modified = false;

        // Add imports if missing (even if menu exists)
        if (! $hasPackageImport || ! $hasWebhookImport || ! $hasWarehouseImport) {
            // Find the last import before "} from "lucide-react"" and add our imports
            // Look for "Workflow," which is typically the last import
            $importsToAdd = [];
            if (! $hasPackageImport) {
                $importsToAdd[] = '    Package,';
            }
            if (! $hasWarehouseImport) {
                $importsToAdd[] = '    Warehouse,';
            }
            if (! $hasWebhookImport) {
                $importsToAdd[] = '    Webhook,';
            }
            
            if (! empty($importsToAdd)) {
                $importString = implode("\n", $importsToAdd);
                $content = preg_replace(
                    '/(    Workflow,)(\s*)(\} from ["\']lucide-react["\'];)/s',
                    "$1\n{$importString}$2$3",
                    $content
                );
                $modified = true;
            }
        }

        // Add menu item if missing
        if (! $hasMenu) {
            $menuItem = ",\n    {\n".
                "        title: \"Shipping\",\n".
                "        icon: Package,\n".
                "        children: [\n".
                "            {\n".
                "                title: \"Providers\",\n".
                "                routeName: \"admin.shipping_provider.index\",\n".
                "                icon: Package,\n".
                "            },\n".
                "            {\n".
                "                title: \"Pickup Addresses\",\n".
                "                routeName: \"admin.pickup_address.index\",\n".
                "                icon: Warehouse,\n".
                "            },\n".
                "            {\n".
                "                title: \"Manual Webhook\",\n".
                "                routeName: \"admin.manual_webhook.create\",\n".
                "                icon: Webhook,\n".
                "            },\n".
                "        ],\n".
                '    }';

            // Find Orders menu item and add Shipping after it
            $content = preg_replace(
                '/(title:\s*["\']Orders["\'][^}]*\}),/s',
                "$1{$menuItem},",
                $content
            );
            $modified = true;
        }

        if (! $modified) {
            throw new \Exception('Already integrated');
        }

        File::put($path, $content);
    }
}
