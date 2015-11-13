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
			{action? : db:migrate, db:rollback, db:reset, db:refresh, db:seed, db:reseed, make:migration, make:console},
			{--usage : Show usage and examples},
			{--force : Force the operation to run when in production},
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
					$this->$handleMethod($section, $action);
					break;
				}
			}
		} else {
			throw new InvalidArgumentException("Argument $command not found");
		}
	}

	/**
	 * Handle db commands
	 * @param  string $section
	 * @param  string $action
	 */
	protected function handleDbCommands($section, $action)
	{
		$method = $section.studly_case($action);
		if (method_exists($this, $method)) {

			if (!Config::has('database.connections.'.$this->connection)) {
				throw new Exception("Connection $this->connection does not exists");
			}
			$this->connection = array_merge(
				['name' => $this->connection],
				Config::get('database.connections.'.$this->connection)
			);

			$this->$method();
		} else {
			throw new InvalidArgumentException("Argument $section:$action not found");
		}
	}

	/**
	 * Handle make commands
	 * @param  string $section
	 * @param  string $action
	 */
	protected function handleMakeCommands($section, $action)
	{
		// Name must be unique accross ALL apps since migrations have no namespace
		$method = $section.studly_case($action);
		if (method_exists($this, $method)) {
			$this->$method();
		} else {
			throw new InvalidArgumentException("Argument $section:$action not found");
		}
	}

	/**
	 * Handle test commands
	 * @param  string $section
	 * @param  string $action
	 */
	protected function handleTestCommands($section, $action)
	{
		// Name must be unique accross ALL apps since migrations have no namespace
		$method = $section.studly_case($action);
		if (str_contains($method, "testPlay")) {
			// Can be test:play or test:play-custom
			$this->testPlay($action);
		} else {
			if (method_exists($this, $method)) {
				$this->$method();
			} else {
				throw new InvalidArgumentException("Argument $section:$action not found");
			}
		}
	}

	/**
	 * Run the database migrations
	 */
	protected function dbMigrate()
	{
		$this->call('migrate', [
			'--database' => $this->connection['name'],
			'--path' => "$this->relativePath/Database/Migrations/",
			'--force' => $force = $this->input->getOption('force')
		]);
	}

	/**
	 * Rollback the last database migration
	 */
	protected function dbRollback()
	{
		$this->call('migrate:rollback', [
			'--database' => $this->connection['name'],
			'--force' => $force = $this->input->getOption('force')
		]);
	}

	/**
	 * Rolls all of the currently applied migrations back
	 */
	protected function dbReset()
	{
		$this->call('migrate:reset', [
			'--database' => $this->connection['name'],
			'--force' => $force = $this->input->getOption('force')
		]);
	}

	/**
	 * Reset and re-run all migrations (no seed)
	 */
	protected function dbRefresh()
	{
		$this->dbReset();
		$this->dbMigrate();
	}

	/**
	 * Seed the database with records
	 */
	protected function dbSeed()
	{
		$this->call('db:seed', [
			'--database' => $this->connection['name'],
			'--class' => $this->seeder,
			'--force' => $force = $this->input->getOption('force')
		]);
	}

	/**
	 * Refresh and seed (reset, migrate, seed)
	 */
	protected function dbReseed()
	{
		$this->dbRefresh();
		$this->dbSeed();
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
	 * Run phpunit tests
	 */
	protected function testRun()
	{
		$params = $this->argument('parameters');
		if (count($params) == 1) {
			// Adding a filter`
			$filter = "--filter=$params[0]";
		}
		passthru("cd ".base_path()." && phpunit $filter $this->path/Tests/");
	}

	/**
	 * Run phpunit test play sandbox
	 * @param  string $action = 'play'
	 */
	protected function testPlay($action = 'play')
	{
		$params = implode(' ', $this->argument('parameters'));
		passthru("cd ".base_path()." && phpunit $this->path/Tests/ $action $params");
	}

	/**
	 * Display usage and examples
	 */
	protected function usage()
	{
		echo "Mrcore app/module helper command usage and examples

As a helper, create yourself a /usr/local/bin/myapp script like so
	/var/www/mrcore5/System/artisan dynatron:vfi:\$1 \"\${@:2}\"

Database Helper Commands
-----------------------
Run the database migrations (will not autocreate the database)
  myapp app db:migrate

Rollback the last database migration
  myapp app db:rollback

Rolls all of the currently applied migrations back
  myapp app db:reset

Reset and re-run all migrations (no seed)
  myapp app db:refresh

Seed the database with records
  myapp app db:seed

Refresh and seed (reset, migrate, seed)
  myapp app db:reseed


Maker Helper Commands
---------------------
Migration file creation
  myapp app make:migration create users
  myapp app make:migration update users votes

Console command creation
  myapp app make:console NewCommand


Testing Helper commands
-----------------------
  myapp app test:run
  myapp app test:run FileFilter
  myapp app test:play
  myapp app test:play-custom
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
