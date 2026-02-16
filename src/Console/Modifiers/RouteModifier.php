<?php

namespace Susheelbhai\Laraship\Console\Modifiers;

use Illuminate\Support\Facades\File;

class RouteModifier
{
    public static function modifyAdminRoutes(): void
    {
        $path = base_path('routes/admin/web.php');
        if (! File::exists($path)) {
            throw new \Exception('Admin routes file not found');
        }

        $content = File::get($path);

        // Check if already added
        if (str_contains($content, "require __DIR__.'/laraship.php'")) {
            throw new \Exception('Already integrated');
        }

        // Find the last Route::resource or Route::get before the closing of admin group
        // Look for common patterns like gallery, newsletter, etc.
        $patterns = [
            "/(Route::resource\('\/gallery'.*?\);)/s",
            "/(Route::get\('\/newsletter'.*?\);)/s",
            "/(Route::resource\('\/promo-code'.*?\);)/s",
        ];

        $inserted = false;
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content)) {
                $content = preg_replace(
                    $pattern,
                    "$1\n        require __DIR__.'/laraship.php';",
                    $content
                );
                $inserted = true;
                break;
            }
        }

        // Fallback: insert before the closing of admin prefix group
        if (! $inserted) {
            $content = preg_replace(
                "/(Route::prefix\('admin'\).*?->group\(function \(\) \{.*?)(    \}\);.*?require __DIR__\.'/s",
                "$1    require __DIR__.'/laraship.php';\n$2",
                $content
            );
        }

        // Add webhook routes outside middleware
        if (! str_contains($content, "require __DIR__.'/laraship_webhook.php'")) {
            $content .= "\n\n// Laraship Webhook Routes - Must be outside admin auth middleware\nrequire __DIR__.'/laraship_webhook.php';\n";
        }

        File::put($path, $content);
    }

    public static function modifyUserRoutes(): void
    {
        $path = base_path('routes/user/web.php');
        if (! File::exists($path)) {
            throw new \Exception('User routes file not found');
        }

        $content = File::get($path);

        // Check if already added
        if (str_contains($content, "require __DIR__.'/laraship.php'")) {
            throw new \Exception('Already integrated');
        }

        // Add at the end of file
        $content .= "\n\n// Laraship user routes\nrequire __DIR__.'/laraship.php';\n";

        File::put($path, $content);
    }

    public static function modifyBootstrapApp(): void
    {
        $path = base_path('bootstrap/app.php');
        if (! File::exists($path)) {
            throw new \Exception('bootstrap/app.php not found');
        }

        $content = File::get($path);

        // Check if already added
        if (str_contains($content, "'webhook/*'")) {
            throw new \Exception('Already integrated');
        }

        // Add CSRF exception
        $content = preg_replace(
            "/(validateCsrfTokens\(except:\s*\[.*?)(\]\))/s",
            "$1,\n        'webhook/*'$2",
            $content
        );

        File::put($path, $content);
    }
}
