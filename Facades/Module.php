<?php namespace Mrcore\Modules\Foundation\Facades;

/**
 * @see \Mrcore\Modules\Foundation\Support\Module
 */
class Module extends \Illuminate\Support\Facades\Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor() { return 'Mrcore\Modules\Foundation\Support\Module'; }

}