<?php

namespace Spatie\WebTinker\Http\Middleware;

use Illuminate\Support\Facades\Gate;

class Authorize
{
    public function handle($request, $next)
    {
        return $this->allowedToUseTinker()
            ? $next($request)
            : abort(403);
    }

    protected function allowedToUseTinker(): bool
    {
        if (! config('custom-custom-web-tinker.enabled', config('custom-web-tinker.enabled'))) {
            return false;
        }

        return Gate::check('viewWebTinkerCustom');
    }
}
