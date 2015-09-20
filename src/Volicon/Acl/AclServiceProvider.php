<?php namespace Volicon\Acl;

use Illuminate\Support\ServiceProvider;

class AclServiceProvider extends ServiceProvider {
	
	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;
	
	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
    {
		
		$this->app->bind('Acl', function() {
		    return new Acl();
		});
	}
	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['acl_install_command'] = $this->app->share(function($app)
		  {
			  return new Commands\InstallCommand;
		  });
		  
		  $this->app['acl_update_command'] = $this->app->share(function($app)
		  {
			  return new Commands\UpdateCommand;
		  });
		  $this->commands('acl_install_command');
		  $this->commands('acl_update_command');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}
