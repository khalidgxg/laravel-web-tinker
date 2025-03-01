<?php

namespace Spatie\WebTinker\Http\Controllers;

use Illuminate\Contracts\Routing\UrlGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
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

    public function getAvailableClasses()
    {
        try {
            $classes = [];
            $count = 0;

            // إضافة الفئات الشائعة في Laravel
            $commonClasses = [
                ['name' => 'User', 'namespace' => 'App\\Models\\User'],
                ['name' => 'Auth', 'namespace' => 'Illuminate\\Support\\Facades\\Auth'],
                ['name' => 'DB', 'namespace' => 'Illuminate\\Support\\Facades\\DB'],
                ['name' => 'Route', 'namespace' => 'Illuminate\\Support\\Facades\\Route'],
                ['name' => 'Storage', 'namespace' => 'Illuminate\\Support\\Facades\\Storage'],
                ['name' => 'Hash', 'namespace' => 'Illuminate\\Support\\Facades\\Hash'],
                ['name' => 'Cache', 'namespace' => 'Illuminate\\Support\\Facades\\Cache'],
                ['name' => 'Session', 'namespace' => 'Illuminate\\Support\\Facades\\Session'],
                ['name' => 'Validator', 'namespace' => 'Illuminate\\Support\\Facades\\Validator'],
                ['name' => 'Carbon', 'namespace' => 'Carbon\\Carbon'],
                ['name' => 'Str', 'namespace' => 'Illuminate\\Support\\Str'],
                ['name' => 'Arr', 'namespace' => 'Illuminate\\Support\\Arr'],
                ['name' => 'Log', 'namespace' => 'Illuminate\\Support\\Facades\\Log'],
                ['name' => 'File', 'namespace' => 'Illuminate\\Support\\Facades\\File'],
                ['name' => 'Event', 'namespace' => 'Illuminate\\Support\\Facades\\Event'],
                ['name' => 'Mail', 'namespace' => 'Illuminate\\Support\\Facades\\Mail'],
                ['name' => 'Notification', 'namespace' => 'Illuminate\\Support\\Facades\\Notification'],
                ['name' => 'Queue', 'namespace' => 'Illuminate\\Support\\Facades\\Queue'],
                ['name' => 'Schema', 'namespace' => 'Illuminate\\Support\\Facades\\Schema'],
                ['name' => 'URL', 'namespace' => 'Illuminate\\Support\\Facades\\URL'],
                ['name' => 'Artisan', 'namespace' => 'Illuminate\\Support\\Facades\\Artisan'],
                ['name' => 'Blade', 'namespace' => 'Illuminate\\Support\\Facades\\Blade'],
                ['name' => 'Config', 'namespace' => 'Illuminate\\Support\\Facades\\Config'],
                ['name' => 'Cookie', 'namespace' => 'Illuminate\\Support\\Facades\\Cookie'],
                ['name' => 'Crypt', 'namespace' => 'Illuminate\\Support\\Facades\\Crypt'],
                ['name' => 'Date', 'namespace' => 'Illuminate\\Support\\Facades\\Date'],
                ['name' => 'Http', 'namespace' => 'Illuminate\\Support\\Facades\\Http'],
                ['name' => 'Password', 'namespace' => 'Illuminate\\Support\\Facades\\Password'],
                ['name' => 'Redirect', 'namespace' => 'Illuminate\\Support\\Facades\\Redirect'],
                ['name' => 'Request', 'namespace' => 'Illuminate\\Http\\Request'],
                ['name' => 'Response', 'namespace' => 'Illuminate\\Http\\Response'],
                ['name' => 'Collection', 'namespace' => 'Illuminate\\Support\\Collection'],
                ['name' => 'Job', 'namespace' => 'Illuminate\\Bus\\Queueable'],
                ['name' => 'Mailable', 'namespace' => 'Illuminate\\Mail\\Mailable'],
                ['name' => 'Notifiable', 'namespace' => 'Illuminate\\Notifications\\Notifiable'],
                ['name' => 'ShouldQueue', 'namespace' => 'Illuminate\\Contracts\\Queue\\ShouldQueue'],
                ['name' => 'Dispatchable', 'namespace' => 'Illuminate\\Foundation\\Bus\\Dispatchable'],
                ['name' => 'InteractsWithQueue', 'namespace' => 'Illuminate\\Queue\\InteractsWithQueue'],
                ['name' => 'SerializesModels', 'namespace' => 'Illuminate\\Queue\\SerializesModels']
            ];

            foreach ($commonClasses as $class) {
                $classes[] = $class;
                $count++;
            }

            // البحث عن النماذج في مجلد app/Models
            $this->scanDirectory(app_path('Models'), 'App\\Models', $classes, $count);

            // البحث عن الوظائف في مجلد app/Jobs
            $this->scanDirectory(app_path('Jobs'), 'App\\Jobs', $classes, $count);

            // البحث عن الإشعارات في مجلد app/Notifications
            $this->scanDirectory(app_path('Notifications'), 'App\\Notifications', $classes, $count);

            // البحث عن التعدادات في مجلد app/Enums
            $this->scanDirectory(app_path('Enums'), 'App\\Enums', $classes, $count);

            // البحث عن الأحداث في مجلد app/Events
            $this->scanDirectory(app_path('Events'), 'App\\Events', $classes, $count);

            // البحث عن المستمعين في مجلد app/Listeners
            $this->scanDirectory(app_path('Listeners'), 'App\\Listeners', $classes, $count);

            // البحث عن البريد في مجلد app/Mail
            $this->scanDirectory(app_path('Mail'), 'App\\Mail', $classes, $count);

            // البحث عن السياسات في مجلد app/Policies
            $this->scanDirectory(app_path('Policies'), 'App\\Policies', $classes, $count);

            // البحث عن القواعد في مجلد app/Rules
            $this->scanDirectory(app_path('Rules'), 'App\\Rules', $classes, $count);

            // البحث عن الموارد في مجلد app/Http/Resources
            $this->scanDirectory(app_path('Http/Resources'), 'App\\Http\\Resources', $classes, $count);

            // البحث عن الطلبات في مجلد app/Http/Requests
            $this->scanDirectory(app_path('Http/Requests'), 'App\\Http\\Requests', $classes, $count);

            // البحث عن المتحكمات في مجلد app/Http/Controllers
            $this->scanDirectory(app_path('Http/Controllers'), 'App\\Http\\Controllers', $classes, $count);

            // البحث عن الوسطاء في مجلد app/Http/Middleware
            $this->scanDirectory(app_path('Http/Middleware'), 'App\\Http\\Middleware', $classes, $count);

            // البحث عن مزودي الخدمات في مجلد app/Providers
            $this->scanDirectory(app_path('Providers'), 'App\\Providers', $classes, $count);

            // إضافة معلومات التصحيح
            app('log')->info('Found ' . $count . ' classes');

            return response()->json([
                'classes' => $classes,
                'count' => $count,
                'app_path' => app_path()
            ]);
        } catch (\Exception $e) {
            app('log')->error('Error in getAvailableClasses: ' . $e->getMessage());
            app('log')->error($e->getTraceAsString());

            return response()->json([
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ], 500);
        }
    }

    /**
     * مسح مجلد بحثًا عن فئات PHP
     *
     * @param string $directory المجلد المراد مسحه
     * @param string $namespace مساحة الاسم الأساسية للمجلد
     * @param array &$classes مصفوفة الفئات التي سيتم تحديثها
     * @param int &$count عدد الفئات التي تم العثور عليها
     * @return void
     */
    private function scanDirectory($directory, $namespace, &$classes, &$count)
    {
        if (!file_exists($directory)) {
            app('log')->info("Directory does not exist: {$directory}");
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($files as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $relativePath = str_replace($directory . '/', '', $file->getPathname());
                $relativePath = str_replace('.php', '', $relativePath);
                $relativePath = str_replace('/', '\\', $relativePath);

                $className = basename($file->getPathname(), '.php');
                $fullNamespace = $namespace . '\\' . $relativePath;

                // تجنب الفئات المكررة
                if (!$this->classExists($classes, $className)) {
                    $classes[] = [
                        'name' => $className,
                        'namespace' => $fullNamespace
                    ];
                    $count++;
                }
            }
        }
    }

    /**
     * التحقق مما إذا كانت الفئة موجودة بالفعل في المصفوفة
     *
     * @param array $classes مصفوفة الفئات
     * @param string $className اسم الفئة للبحث عنها
     * @return bool
     */
    private function classExists($classes, $className)
    {
        foreach ($classes as $class) {
            if ($class['name'] === $className) {
                return true;
            }
        }
        return false;
    }
}
