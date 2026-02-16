<?php

namespace Susheelbhai\Laraship\Console\Modifiers;

use Illuminate\Support\Facades\File;

class SeederModifier
{
    public static function modifyDatabaseSeeder(): void
    {
        $path = database_path('seeders/DatabaseSeeder.php');
        if (! File::exists($path)) {
            throw new \Exception('DatabaseSeeder.php not found');
        }

        $content = File::get($path);

        // Check if already added
        if (str_contains($content, 'LarashipSeeder')) {
            throw new \Exception('Already integrated');
        }

        // Add to call array
        $pattern = '/(\$this->call\(\[)(.*?)(\]\);)/s';
        if (preg_match($pattern, $content, $matches)) {
            $newCall = $matches[1].$matches[2].",\n            \\Database\\Seeders\\Laraship\\LarashipSeeder::class,".$matches[3];
            $content = preg_replace($pattern, $newCall, $content);
        } else {
            // If no call array exists, add one
            $pattern = '/(public function run\(\): void\s*\{)/';
            $replacement = "$1\n        \$this->call([\n            \\Database\\Seeders\\Laraship\\LarashipSeeder::class,\n        ]);";
            $content = preg_replace($pattern, $replacement, $content);
        }

        File::put($path, $content);
    }
}
