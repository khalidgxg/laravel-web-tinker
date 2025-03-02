<?php

namespace Spatie\WebTinker\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'web-tinker-custom:install';

    protected $description = 'Install all of the Web Tinker Custom resources';

    public function handle()
    {
        $this->comment('Publishing Web Tinker Custom Assets...');

        $this->callSilent('vendor:publish', ['--tag' => 'web-tinker-custom-assets']);

        $this->info('Web tinker custom version installed successfully.');
    }
}
