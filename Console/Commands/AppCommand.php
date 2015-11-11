<?php namespace Mrcore\Foundation\Console\Commands;

use App;
use File;
use Config;
use Exception;
use InvalidArgumentException;
use Illuminate\Console\Command;

/**
 * Mrcore app/module helper command
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class AppCommand extends Command
{
	protected $signature;
	protected $description = "Mrcore app/module helper command";
	protected $app;
	protected $ns;
	protected $path;
	protected $relativePath;
	protected $connection;
	protected $seeder;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->signature = $this->signature.'
			{action? : db:migrate, db:seed, db:reseed, db:rollback, db:refresh, make:migration},
			{--usage : Show usage and examples},
			{parameters?* : Any number of parameters for the specified action},
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
		// Ex: db:migrate, make:migration
		$command = $this->argument('action');

		if ($this->option('usage') || !$command) return $this->usage();

		if (!str_contains($command, ':')) throw new InvalidArgumentException();

		list($section, $action) = explode(':', $command);

		$handleMethod = 'handle'.studly_case($section).'Commands';
		if (method_exists($this, $handleMethod)) {

			// Determine valid path to app
			foreach ($this->path as $path) {
				if (file_exists(base_path($path))) {

					// Found valid path
					$this->relativePath = $path;
					$this->path = realpath(base_path($path));

					// Execute method
					$this->$handleMethod($action);
				}
			}
		} else {
			throw new InvalidArgumentException("Argument $command not found");
		}
	}

	/**
	 * Handle db commands
	 * @param  string $action
	 */
	protected function handleDbCommands($action)
	{
		$method = "db".studly_case($action);
		if (method_exists($this, $method)) {

			if (!Config::has('database.connections.'.$this->connection)) {
				throw new Exception("Connection $this->connection does not exists");
			}
			$this->connection = array_merge(
				['name' => $this->connection],
				Config::get('database.connections.'.$this->connection)
			);

			$this->$method();
		}
	}

	/**
	 * Handle make commands
	 * @param  string $action
	 */
	protected function handleMakeCommands($action)
	{
		// Name must be unique accross ALL apps since migrations have no namespace
		$method = "make".studly_case($action);
		if (method_exists($this, $method)) {
			$this->$method();
		}
	}

	/**
	 * Migrate database
	 */
	protected function dbMigrate()
	{
		$this->call('migrate', [
			'--database' => $this->connection['name'],
			'--path' => "$this->relativePath/Database/Migrations/"
		]);
	}

	/**
	 * Seed database
	 */
	protected function dbSeed()
	{
		if (App::environment() === 'production') {
			throw new Exception("You cannot seed in production");
		}
		$this->call('db:seed', [
			'--database' => $this->connection['name'],
			'--class' => $this->seeder
		]);
	}

	/**
	 * Refresh then seed database
	 */
	protected function dbReseed()
	{
		if (App::environment() === 'production') {
			throw new Exception("You cannot seed in production");
		}
		$this->dbRefresh();
		$this->dbSeed();
	}

	/**
	 * Rollback migrations
	 */
	protected function dbRollback()
	{
		$this->call('migrate:rollback', [
			'--database' => $this->connection['name']
		]);
	}

	/**
	 * Refresh migrations (rollback all, then migrate)
	 */
	protected function dbRefresh()
	{
		if (App::environment() === 'production') {
			throw new Exception("You cannot rollback in production");
		}
		$this->dbRollback();
		$this->dbMigrate();
	}

	/**
	 * Make console command file
	 */
	protected function makeConsole()
	{
		//./artisan vendor:myapp:app make:console NewCommand
		$params = $this->argument('parameters');
		if (count($params) != 1) throw new InvalidArgumentException();
		$name = $params[0];

		$path = "$this->path/Console/Commands";
		$command = "$path/$name.php";
		$tmp = app_path('Console/Commands/MrcoreStubCommand.php');

		if (file_exists($command)) {
			throw new Exception("Command $command already exists");
		}

		// Create command
		// Use temp name so we don't override any existing commands in laravel app dir
		$this->call('make:console', [
			'name' => 'MrcoreStubCommand'
		]);

		// Move command to mrcore app folder
		if (!file_exists($path)) exec("mkdir -p $path");
		File::move($tmp, $command);

		// Replace command content with proper namespace
		$ns = str_replace("\\", "\\\\\\", $this->ns);
		$signature = strtolower(str_replace("\\", ":", $this->ns)).":".str_replace("command", "", strtolower($name));
		$this->sed("App\\\\Console\\\\Commands", "$ns\\\\Console\\\\Commands", $command);
		$this->sed("MrcoreStubCommand", $name, $command);
		$this->sed("command:name", $signature, $command);
	}

	/**
	 * Make migration file
	 */
	protected function makeMigration()
	{
		//./artisan vendor:myapp:app make:migration create users
		//./artisan vendor:myapp:app make:migration update users votes
		$params = $this->argument('parameters');
		if (count($params) != 2 & count($params) != 3) throw new InvalidArgumentException();
		$action = $params[0];
		$table = $params[1];

		if ($action == 'create') {
			// Create new table
			$this->call('make:migration', [
				'name' => "create_${table}_".$this->app,
				'--path' => "$this->relativePath/Database/Migrations/",
				'--create' => $table
			]);

		} elseif ($action == 'update') {
			// Update table add column
			$column = $params[2];
			$this->call('make:migration', [
				'name' => "update_${table}_add_${column}_".$this->app,
				'--path' => "$this->relativePath/Database/Migrations/",
				'--table' => $table
			]);

		} else {
			throw new InvalidArgumentException();
		}
	}

	/**
	 * Display usage and examples
	 */
	protected function usage()
	{
		echo "Mrcore app/module helper command usage and examples

Database Helper Commands
-----------------------
Migrate a database (will NOT create the database if not exist)
  ./artisan vendor:myapp:app db:migrate

Seed database tables
  ./artisan vendor:myapp:app db:seed

Rollback, Migrate, Seed database (refresh + seed)
  ./artisan vendor:myapp:app db:reseed

Rollback last migration
  ./artisan vendor:myapp:app db:rollback

Rollback, Migrate (no seed)
  ./artisan vendor:myapp:app db:refresh


Maker Helper Commands
---------------------
Migration file creation
  ./artisan vendor:myapp:app make:migration create users
  ./artisan vendor:myapp:app make:migration update users votes

Console command creation
  ./artisan vendor:myapp:app make:console NewCommand
";
	}

	/**
	 * Sed helper
	 * @param  string $search
	 * @param  string $replace
	 * @param  string $file
	 * @return void
	 */
	protected function sed($search, $replace, $file)
	{
		exec("sed -i 's`$search`$replace`g' $file");
	}

}
