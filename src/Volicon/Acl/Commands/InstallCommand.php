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
		$this->output->writeln('Publish config and migration');
        \Artisan::call('vendor:publish', array('--provider' => 'Volicon\Acl\AclServiceProvider'), $this->output);
		
		//$this->call('vendor:publish', array('--provider' => 'Volicon\Acl\AclServiceProvider', '--tag' => 'config'));
		//$this->call('vendor:publish', array('--provider' => 'Volicon\Acl\AclServiceProvider', '--tag' => 'migrations'));
	}
}
