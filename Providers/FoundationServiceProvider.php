<?php namespace Mrcore\Modules\Foundation\Providers;

use View;
use Input;
use Config;
use Layout;
use Module;
use Mrcore\Modules\Foundation\Support\ServiceProvider;

class FoundationServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application services.
	 * @param  Illuminate\Routing\Router $router
	 *
	 * @return void
	 */
	public function boot()
	{
		// Mrcore Module Tracking
		Module::trace(get_class(), __function__);

		// Load our custom macros
		require __DIR__.'/../Support/Macros.php';

		// Configure layout modes
		$simpleMode = Input::get('simple');
		if (isset($simpleMode) || Input::get('viewmode') == 'simple') {
			Layout::mode('simple');
		}
		$rawMode = Input::get('raw');
		if (isset($rawMode) || Input::get('viewmode') == 'raw') {
			Layout::mode('raw');
		}
		$defaultMode = Input::get('default');
		if (isset($defaultMode) || Input::get('viewmode') == 'default') {
			Layout::mode('default');
		}

	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{
		// Register Foundation Facades
		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
		$loader->alias('Module', 'Mrcore\Modules\Foundation\Facades\Module');
		$loader->alias('Layout', 'Mrcore\Modules\Foundation\Facades\Layout');

		// Register UrlServiceProvider for https ssl termination Support
		$this->app->register('Mrcore\Modules\Foundation\Providers\UrlServiceProvider');

		// Mrcore Module Tracking
		Module::trace(get_class(), __function__);

		// Register all enabled mrcore modules
		$modules = Module::register();
	}

}
