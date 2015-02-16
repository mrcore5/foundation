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
		// Load all modules views, routes and theme information
		// NOTE: autloaders already loaded from Module::register()
		#dd(Module::all());

		Module::loadViews();
		Module::loadRoutes();
		Module::configureThemes();

		#dd(Module::trace());		

		// Next middleware
		return $next($request);

	}

}
