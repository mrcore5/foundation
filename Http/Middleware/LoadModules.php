<?php namespace Mrcore\Modules\Foundation\Http\Middleware;

use Module;
use Closure;

class LoadModules {

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
		Module::loadViews();
		Module::loadRoutes();

		// Next middleware
		return $next($request);

	}

}
