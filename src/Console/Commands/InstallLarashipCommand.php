<?php

namespace Susheelbhai\Laraship\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Susheelbhai\Laraship\Console\Modifiers\ControllerModifier;
use Susheelbhai\Laraship\Console\Modifiers\FrontendModifier;
use Susheelbhai\Laraship\Console\Modifiers\ModelModifier;
use Susheelbhai\Laraship\Console\Modifiers\RouteModifier;
use Susheelbhai\Laraship\Console\Modifiers\SeederModifier;

class InstallLarashipCommand extends Command
{
    protected $signature = 'laraship:install
                            {--force : Overwrite existing files}
                            {--no-migrations : Skip publishing migrations}';

    protected $description = 'Install Laraship package - publish assets and modify project files';

    protected array $modifiedFiles = [];

    protected array $errors = [];

    public function handle(): int
    {
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║   Laraship Package Installer          ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();

        // Step 1: Publish package assets
        $this->publishAssets();

        // Step 2: Run migrations
        $this->runMigrations();

        // Step 3: Run seeders
        $this->runSeeders();

        // Step 4: Modify project files
        if ($this->option('no-interaction') || $this->confirm('Do you want to automatically modify project files?', true)) {
            $this->modifyProjectFiles();
        } else {
            $this->warn('⚠ Skipping project file modifications. You will need to manually integrate the package.');
            $this->info('See LARASHIP_INSTALLATION_GUIDE.md for manual integration steps.');
        }

        // Step 5: Display summary
        $this->displaySummary();

        return self::SUCCESS;
    }

    protected function publishAssets(): void
    {
        $this->info('📦 Publishing package assets...');
        $this->newLine();

        $tags = [
            'laraship-config' => 'Configuration',
            'laraship-migrations' => 'Migrations',
            'laraship-controllers' => 'Controllers',
            'laraship-requests' => 'Form Requests',
            'laraship-components' => 'React Components',
            'laraship-routes' => 'Routes',
            'laraship-seeders' => 'Seeders',
        ];

        foreach ($tags as $tag => $label) {
            if ($tag === 'laraship-migrations' && $this->option('no-migrations')) {
                continue;
            }

            $this->comment("  Publishing {$label}...");
            $this->call('vendor:publish', [
                '--tag' => $tag,
                '--force' => $this->option('force'),
            ]);
        }

        // Fix namespaces in published controllers
        $this->fixPublishedNamespaces();

        $this->newLine();
        $this->info('✓ Assets published successfully!');
        $this->newLine();
    }

    protected function fixPublishedNamespaces(): void
    {
        $this->comment('  Fixing namespaces...');

        $files = [
            ['path' => app_path('Http/Controllers/Admin/ShippingProviderController.php'), 'namespace' => 'App\Http\Controllers\Admin'],
            ['path' => app_path('Http/Controllers/Admin/OrderShipmentController.php'), 'namespace' => 'App\Http\Controllers\Admin'],
            ['path' => app_path('Http/Controllers/Admin/ManualWebhookController.php'), 'namespace' => 'App\Http\Controllers\Admin'],
            ['path' => app_path('Http/Controllers/User/OrderShipmentController.php'), 'namespace' => 'App\Http\Controllers\User'],
        ];

        foreach ($files as $file) {
            $this->fixControllerNamespace($file['path'], $file['namespace']);
        }

        $this->fixRequestNamespace(app_path('Http/Requests/ShippingProviderRequest.php'));
        $this->fixRequestNamespace(app_path('Http/Requests/ManualWebhookRequest.php'));
    }

    protected function fixControllerNamespace(string $path, string $namespace): void
    {
        if (! File::exists($path)) {
            return;
        }

        $content = File::get($path);
        $content = str_replace('namespace Susheelbhai\Laraship\Http\Controllers;', "namespace {$namespace};", $content);
        $content = str_replace('use Illuminate\Routing\Controller;', 'use App\Http\Controllers\Controller;', $content);
        $content = str_replace('use Susheelbhai\Laraship\Http\Requests\ShippingProviderRequest;', 'use App\Http\Requests\ShippingProviderRequest;', $content);
        $content = str_replace('use Susheelbhai\Laraship\Http\Requests\ManualWebhookRequest;', 'use App\Http\Requests\ManualWebhookRequest;', $content);

        // Fix class name for User OrderShipmentController
        if (str_contains($path, 'User/OrderShipmentController.php')) {
            $content = str_replace('class UserOrderShipmentController extends Controller', 'class OrderShipmentController extends Controller', $content);
        }

        File::put($path, $content);
    }

    protected function fixRequestNamespace(string $path): void
    {
        if (! File::exists($path)) {
            return;
        }

        $content = File::get($path);
        $content = str_replace('namespace Susheelbhai\Laraship\Http\Requests;', 'namespace App\Http\Requests;', $content);
        File::put($path, $content);
    }

    protected function runMigrations(): void
    {
        if ($this->option('no-interaction') || $this->confirm('Run migrations now?', true)) {
            $this->info('🔄 Running migrations...');
            $this->call('migrate');
            $this->info('✓ Migrations completed!');
            $this->newLine();
        }
    }

    protected function runSeeders(): void
    {
        if ($this->option('no-interaction') || $this->confirm('Run seeders to create sample shipping providers?', true)) {
            $this->info('🌱 Running seeders...');
            $this->call('db:seed', ['--class' => 'Database\\Seeders\\Laraship\\LarashipSeeder']);
            $this->info('✓ Seeders completed!');
            $this->newLine();
        }
    }

    protected function modifyProjectFiles(): void
    {
        $this->info('🔧 Modifying project files...');
        $this->newLine();

        $modifications = [
            'Database Seeder' => [
                'callback' => fn () => SeederModifier::modifyDatabaseSeeder(),
                'path' => database_path('seeders/DatabaseSeeder.php'),
            ],
            'Product Model' => [
                'callback' => fn () => ModelModifier::addProductTrait(),
                'path' => app_path('Models/Product.php'),
            ],
            'Order Model' => [
                'callback' => fn () => ModelModifier::addOrderTrait(),
                'path' => app_path('Models/Order.php'),
            ],
            'Admin Order Controller' => [
                'callback' => fn () => ControllerModifier::modifyAdminOrderController(),
                'path' => app_path('Http/Controllers/Admin/OrderController.php'),
            ],
            'User Order Controller' => [
                'callback' => fn () => ControllerModifier::modifyUserOrderController(),
                'path' => app_path('Http/Controllers/User/OrderController.php'),
            ],
            'Admin Routes' => [
                'callback' => fn () => RouteModifier::modifyAdminRoutes(),
                'path' => base_path('routes/admin/web.php'),
            ],
            'User Routes' => [
                'callback' => fn () => RouteModifier::modifyUserRoutes(),
                'path' => base_path('routes/user/web.php'),
            ],
            'Bootstrap App' => [
                'callback' => fn () => RouteModifier::modifyBootstrapApp(),
                'path' => base_path('bootstrap/app.php'),
                'skip' => true, // Skip by default since most projects already have webhook/* in CSRF exceptions
            ],
            'Admin Order Show Page' => [
                'callback' => fn () => FrontendModifier::modifyAdminOrderShowPage(),
                'path' => resource_path('js/pages/admin/resources/order/show.tsx'),
            ],
            'User Order Show Page' => [
                'callback' => fn () => FrontendModifier::modifyUserOrderShowPage(),
                'path' => resource_path('js/pages/user/orders/show.tsx'),
            ],
            'Admin Sidebar' => [
                'callback' => fn () => FrontendModifier::modifyAdminSidebar(),
                'path' => resource_path('data/js/sidebar_admin.ts'),
            ],
        ];

        foreach ($modifications as $name => $config) {
            // Skip if marked to skip
            if (isset($config['skip']) && $config['skip']) {
                $this->line("  ⊘ Skipped {$name} (already configured in project)");

                continue;
            }

            if ($this->option('no-interaction') || $this->confirm("  Modify {$name}?", true)) {
                try {
                    $config['callback']();
                    $this->modifiedFiles[] = $name;
                    $relativePath = str_replace(base_path().'/', '', $config['path']);
                    $this->line("  ✓ {$name} modified");
                    $this->comment("    → {$relativePath}");

                    // Show component usage instructions for Order Show Pages
                    if ($name === 'Admin Order Show Page') {
                        $this->newLine();
                        $this->info('    📋 Manual Step Required:');
                        $this->line('    Add this component where you want to display shipping information:');
                        $this->comment('    <ShippingSection orderId={order.id} shipment={order.shipment} />');
                        $this->newLine();
                    } elseif ($name === 'User Order Show Page') {
                        $this->newLine();
                        $this->info('    📋 Manual Step Required:');
                        $this->line('    Add this component where you want to display shipping information:');
                        $this->comment('    <UserShippingSection orderId={order.id} shipment={order.shipment} />');
                        $this->newLine();
                    }
                } catch (\Exception $e) {
                    if ($e->getMessage() === 'Already integrated') {
                        $this->warn('    Already integrated');
                    } else {
                        $this->errors[] = "{$name}: {$e->getMessage()}";
                        $this->error("  ✗ Failed to modify {$name}: {$e->getMessage()}");
                    }
                }
            } else {
                $this->line("  ⊘ Skipped {$name}");
            }
        }

        $this->newLine();
    }

    protected function displaySummary(): void
    {
        $this->newLine();
        $this->info('╔════════════════════════════════════════╗');
        $this->info('║   Installation Summary                 ║');
        $this->info('╚════════════════════════════════════════╝');
        $this->newLine();

        if (! empty($this->modifiedFiles)) {
            $this->info('✓ Modified Files ('.count($this->modifiedFiles).')');
            foreach ($this->modifiedFiles as $file) {
                $this->line("  • {$file}");
            }
            $this->newLine();
        }

        if (! empty($this->errors)) {
            $this->error('✗ Errors ('.count($this->errors).')');
            foreach ($this->errors as $error) {
                $this->line("  • {$error}");
            }
            $this->newLine();
        }

        $this->comment('Next Steps:');
        $this->line('1. Run: npm run build (or npm run dev)');
        $this->line('2. Run: php artisan optimize:clear');
        $this->line('3. Configure warehouse address in config/laraship.php');
        $this->line('4. Visit /admin/shipping_provider to manage providers');
        $this->newLine();

        $this->info('✓ Laraship installation completed!');
        $this->newLine();
    }
}
