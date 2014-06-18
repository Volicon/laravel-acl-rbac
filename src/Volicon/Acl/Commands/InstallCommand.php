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
		
		$path = 'vendor\volicon\acl\src\config';
		
		$a = $this->call('config:publish', array('--path' => $path, 'package' => 'volicon/acl'));
		
		$a = $this->call('migrate', array('--bench' => 'volicon/acl'));
	}
}
