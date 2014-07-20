<?php namespace Volicon\Acl\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command {
	
	protected $name = 'acl:install';
	
	protected $description = 'Create Acl table structure.';
	
	/**
	 * Execute the console command.
	 *
	 * @return void
	 */
	public function fire()
	{
		
		$base_dir = dirname(dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))));
		$base_dir = strrchr($base_dir, DIRECTORY_SEPARATOR);
		$base_dir = substr($base_dir, 1);
		
		$path = $base_dir.'\volicon\acl\src\config';
		
		$this->call('config:publish', array('--path' => $path, 'package' => 'volicon/acl'));
		
		$this->call('migrate', array('--bench' => 'volicon/acl'));
	}
}
