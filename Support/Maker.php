<?php namespace Mrcore\Foundation\Support;

use App;
use Config;
use Exception;
use Illuminate\Console\Command;

/**
 * Package maker helper
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Maker extends Command
{
	protected $signature;
	protected $description = "Package maker helper";
	protected $app;
	protected $path;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->signature = $this->signature.'
			{make : migration},
			{parameters* : Any number of parameters for the specified make command}
		';
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle()
	{
		$method = $this->argument('make');
		if (method_exists($this, $method)) {

			foreach ($this->path as $path) {
				if (file_exists(base_path($path))) {
					$this->path = realpath(base_path($path));
					$this->$method();
				}
			}

		} else {
			$this->error("$method() method not found");
		}
	}

	/**
	 * Migrate database
	 */
	protected function migration()
	{
		// make:migrate create_test_table --create=test



		dd($this->path);

		dd($this->argument());
		dd($this->option());
		dump("ok, migration");
	}

}
