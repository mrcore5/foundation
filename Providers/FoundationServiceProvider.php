<?php namespace Mrcore\Modules\Foundation\Providers;

use Input;
use Config;
use Layout;
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
	 *
	 * @return void
	 */
	public function boot()
	{

		// Populate Layout Facade Class
		$defaultMode = Input::get('default');
		if (isset($defaultMode) || Input::get('viewmode') == 'default') {
			Layout::mode('default');
		}
		$simpleMode = Input::get('simple');
		if (isset($simpleMode) || Input::get('viewmode') == 'simple') {
			Layout::mode('simple');
		}
		$rawMode = Input::get('raw');
		if (isset($rawMode) || Input::get('viewmode') == 'raw') {
			Layout::mode('raw');
		}

	}

	/**
	 * Register the application services.
	 *
	 * @return void
	 */
	public function register()
	{

		// Facades
		$loader = \Illuminate\Foundation\AliasLoader::getInstance();
		$loader->alias('Layout', 'Mrcore\Modules\Foundation\Facades\Layout');

		// Register Themes Here instead of config/app.php because of Support/AssetProvider
		$themes = Config::get('theme.themes');
		if (isset($themes)) {
			foreach ($themes as $theme) {
				$this->app->register("$theme[namespace]\Providers\ThemeServiceProvider");
			}
		}
	}

}
