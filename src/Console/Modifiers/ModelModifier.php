<?php

namespace Susheelbhai\Laraship\Console\Modifiers;

use Illuminate\Support\Facades\File;

class ModelModifier
{
    public static function addProductTrait(): void
    {
        $path = app_path('Models/Product.php');
        if (! File::exists($path)) {
            throw new \Exception('Product model not found');
        }

        $content = File::get($path);

        // Check if already added
        if (str_contains($content, 'HasShippingDimensions')) {
            throw new \Exception('Already integrated');
        }

        // Add use statement
        $useStatement = 'use Susheelbhai\Laraship\Traits\HasShippingDimensions;';
        if (! str_contains($content, $useStatement)) {
            $content = preg_replace(
                '/(namespace App\\\\Models;.*?)(class)/s',
                "$1\n{$useStatement}\n\n$2",
                $content
            );
        }

        // Add trait to class - find existing use statement and add to it
        $content = preg_replace(
            '/(use\s+[^;]+);(\s*)(\/\/|protected|public|private|\})/m',
            '$1, HasShippingDimensions;$2$3',
            $content,
            1
        );

        File::put($path, $content);
    }

    public static function addOrderTrait(): void
    {
        $path = app_path('Models/Order.php');
        if (! File::exists($path)) {
            throw new \Exception('Order model not found');
        }

        $content = File::get($path);

        // Check if already added
        if (str_contains($content, 'HasShipment')) {
            throw new \Exception('Already integrated');
        }

        // Add use statement
        $useStatement = 'use Susheelbhai\Laraship\Traits\HasShipment;';
        if (! str_contains($content, $useStatement)) {
            $content = preg_replace(
                '/(namespace App\\\\Models;.*?)(class)/s',
                "$1\n{$useStatement}\n\n$2",
                $content
            );
        }

        // Add trait to class - find existing use statement and add to it
        $content = preg_replace(
            '/(use\s+[^;]+);(\s*)(\/\/|protected|public|private|\})/m',
            '$1, HasShipment;$2$3',
            $content,
            1
        );

        File::put($path, $content);
    }
}
