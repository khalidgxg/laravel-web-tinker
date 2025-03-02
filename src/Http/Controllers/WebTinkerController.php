<?php

namespace Spatie\WebTinker\Http\Controllers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Spatie\WebTinker\Tinker;

class WebTinkerController
{
    public function index()
    {
        return view('web-tinker-custom::web-tinker', [
            'path' => app(UrlGenerator::class)->to(config('web-tinker-custom.path', config('web-tinker.path'))),
        ]);
    }

    public function execute(Request $request, Tinker $tinker)
    {
        $validated = $request->validate([
            'code' => 'required',
        ]);

        return $tinker->execute($validated['code']);
    }

    public function getSuggestions()
    {
        // Get all loaded classes
        $classes = get_declared_classes();
        $suggestions = [];

        foreach ($classes as $class) {
            if (strpos($class, 'App\\') === 0 || strpos($class, 'Illuminate\\') === 0) {
                $suggestions[] = $class;

                // Add public methods
                $methods = get_class_methods($class);
                if ($methods) {
                    foreach ($methods as $method) {
                        $suggestions[] = "$class::$method()";
                    }
                }
            }
        }

        return response()->json([
            'suggestions' => array_unique($suggestions)
        ]);
    }

    public function getAvailableClasses()
    {
        $result = [];

        // Common Laravel classes
        $laravelClasses = [
            ['name' => 'User', 'namespace' => 'App\\Models\\User'],
            ['name' => 'Auth', 'namespace' => 'Illuminate\\Support\\Facades\\Auth'],
            ['name' => 'DB', 'namespace' => 'Illuminate\\Support\\Facades\\DB'],
            ['name' => 'Route', 'namespace' => 'Illuminate\\Support\\Facades\\Route'],
            ['name' => 'Storage', 'namespace' => 'Illuminate\\Support\\Facades\\Storage'],
            ['name' => 'Hash', 'namespace' => 'Illuminate\\Support\\Facades\\Hash'],
            ['name' => 'Cache', 'namespace' => 'Illuminate\\Support\\Facades\\Cache'],
            ['name' => 'Session', 'namespace' => 'Illuminate\\Support\\Facades\\Session'],
            ['name' => 'Validator', 'namespace' => 'Illuminate\\Support\\Facades\\Validator'],
            ['name' => 'Event', 'namespace' => 'Illuminate\\Support\\Facades\\Event'],
            ['name' => 'Log', 'namespace' => 'Illuminate\\Support\\Facades\\Log'],
            ['name' => 'Carbon', 'namespace' => 'Carbon\\Carbon'],
            ['name' => 'Collection', 'namespace' => 'Illuminate\\Support\\Collection'],
            ['name' => 'Str', 'namespace' => 'Illuminate\\Support\\Str'],
            ['name' => 'Arr', 'namespace' => 'Illuminate\\Support\\Arr']
        ];

        $result = array_merge($result, $laravelClasses);

        try {
            // Scan app/Models directory for model classes
            $modelsPath = app_path('Models');
            if (File::isDirectory($modelsPath)) {
                $modelFiles = File::files($modelsPath);

                foreach ($modelFiles as $file) {
                    $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    if ($className && $className !== 'index' && substr($className, 0, 1) !== '.') {
                        $result[] = [
                            'name' => $className,
                            'namespace' => 'App\\Models\\' . $className
                        ];
                    }
                }
            }

            // Legacy app directory models
            $legacyModelsPath = app_path();
            if (File::isDirectory($legacyModelsPath)) {
                $modelFiles = File::files($legacyModelsPath);

                foreach ($modelFiles as $file) {
                    $className = pathinfo($file->getFilename(), PATHINFO_FILENAME);
                    if ($className && $className !== 'index' &&
                        !in_array($className, ['User', 'Console', 'Exceptions', 'Http', 'Providers']) &&
                        substr($className, 0, 1) !== '.' &&
                        substr($file->getFilename(), -4) === '.php') {
                        $result[] = [
                            'name' => $className,
                            'namespace' => 'App\\' . $className
                        ];
                    }
                }
            }

            // Get all loaded classes
            $loadedClasses = get_declared_classes();
            foreach ($loadedClasses as $class) {
                // Only include App namespace classes
                if (strpos($class, 'App\\') === 0) {
                    $parts = explode('\\', $class);
                    $className = end($parts);

                    // Check if class already exists in result
                    $exists = false;
                    foreach ($result as $item) {
                        if ($item['name'] === $className) {
                            $exists = true;
                            break;
                        }
                    }

                    if (!$exists) {
                        $result[] = [
                            'name' => $className,
                            'namespace' => $class
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            // Log error but continue with default classes
            Log::error('Error scanning for classes: ' . $e->getMessage());
        }

        return response()->json([
            'classes' => $result,
            'count' => count($result),
            'app_path' => app_path(),
            'version' => '1.0.1'
        ]);
    }
}
