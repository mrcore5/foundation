<?php namespace Mrcore\Modules\Foundation\Support;

use App;
use Config;
use Exception;
use Illuminate\Console\Command;

/**
 * Package migration and seed command helper
 * @copyright 2015 Matthew Reschke
 * @license http://mreschke.com/license/mit
 * @author Matthew Reschke <mail@mreschke.com>
 */
class Migrations extends Command
{
	protected $name;
	protected $package;
	protected $version = "1.1";
	protected $description = "Migrate and seed mrcore package database";
	protected $signature;
	protected $connection;
	protected $path;
	protected $seeder;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->signature = $this->signature.'
			{action : migrate, seed, reseed, rollback, refresh}
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
		$method = $this->argument('action');
		if (method_exists($this, $method)) {

			if (!Config::has('database.connections.'.$this->connection)) {
				throw new Exception("Connection $this->connection does not exists");
			}
			$this->connection = array_merge(
				['name' => $this->connection],
				Config::get('database.connections.'.$this->connection)
			);

			foreach ($this->path as $path) {
				if (file_exists(base_path($path))) {
					$this->path = $path;
					break;
				}
			}

			$this->$method();

		} else {
			$this->error("$method() method not found");
		}
	}

	/**
	 * Migrate database
	 */
	protected function migrate()
	{
		$this->createDatabase();
		$this->call('migrate', [
			'--database' => $this->connection['name'],
			'--path' => "$this->path/Database/Migrations/"
		]);
	}

	/**
	 * Seed database
	 */
	protected function seed()
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
	protected function reseed()
	{
		if (App::environment() === 'production') {
			throw new Exception("You cannot seed in production");
		}
		$this->refresh();
		$this->seed();
	}

	/**
	 * Rollback migrations
	 */
	protected function rollback()
	{
		$this->call('migrate:rollback', [
			'--database' => $this->connection['name']
		]);
	}

	/**
	 * Refresh migrations (rollback all, then migrate)
	 */
	protected function refresh()
	{
		if (App::environment() === 'production') {
			throw new Exception("You cannot rollback in production");
		}
		$this->rollback();
		$this->migrate();
	}


	/**
	 * Create database if not exists
	 */
	protected function createDatabase()
	{
		// Laravel DB cannot connect without a valid database, so this is a chicken egg problem
		// Use raw mysql to create the database first
		$conn = $this->connection;
		// Create connection
		$handle = new \mysqli($conn['host'], $conn['username'], $conn['password']);
		if ($handle->connect_error) {
			dd("Connection failed: ".$handle->connect_error);
		}
		// Create database
		$sql = "CREATE DATABASE IF NOT EXISTS $conn[database]";
		if ($handle->query($sql) === TRUE) {
			$this->info("Database $conn[database] created successfully");
		} else {
			dd("Error creating database $conn[database]: ".$handle->error);
		}
		$handle->close();
	}

}
