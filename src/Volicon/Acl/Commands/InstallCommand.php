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
		
		$package_path = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
        $package_name = substr($package_path, strrpos($package_path, DIRECTORY_SEPARATOR)+1);
		
		$path = $package_path.'/src/config';
		
		$this->call('config:publish', array('--path' => $path, 'package' => 'volicon/acl'));
		
		$this->call('migrate', array('--package' => 'volicon/'.$package_name));
	}
}
