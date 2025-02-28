<?php

namespace Spatie\WebTinker\Http\Controllers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Spatie\WebTinker\Tinker;

class WebTinkerController
{
    public function index()
    {
        return view('web-tinker::web-tinker', [
            'path' => app(UrlGenerator::class)->to(config('web-tinker.path')),
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
}
