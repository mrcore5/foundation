<?php namespace Mrcore\Foundation\Http\Middleware;

use Module;
use Closure;

class LoadModules
{

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        // Load all modules views and routes
        // NOTE: autloaders have already beed loaded from Module::register() in FoundationServiceProvider
        // NOTE: If running in console (artisan, testing, queue workers), this middle ware never runs, becuase its HTTP only.
        // but loadViews() may be needed for console apps too (think sending emails using view templates).
        // So I loadViews() in the FoundationServiceProvider boot() function if running in console mode!
        // We must do this here for HTTP and not always in the provider because dynamic apps will register
        // their own views too, so we need all registered before calling loadViews(), and rememver the route
        // analyze must be middleware as it does not work at the provider level.
        Module::loadViews();
        Module::loadRoutes();

        // Next middleware
        return $next($request);
    }
}
