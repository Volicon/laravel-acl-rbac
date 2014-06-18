Volicon ACL
===========

ACL component for Laravel 4.

Work best and transparent for resources.

## Installation

Add to composer.json: 

```
"require": {
		"volicon/acl": "dev-master",
},
"minimum-stability": "dev"
```

Add it to the app\config\app.php:

```
'providers' => array(

	'Volicon\Acl\AclServiceProvider',

),

'aliases' => array(

	'Acl'			  => 'Volicon\Acl\Facades\Acl',

)

```

Run install:

```
php artisan acl:install
```

Change config in app\config\packages\volicon\acl\config.php and then run:

```
php artisan acl:update
```

Add it to filters.php for example:

```
Route::filter('auth', function()
{
	if (Auth::guest()) {
		return Redirect::to('/login.html');
	}
	if(Acl::check()->result == Acl::DISALLOW ) return Response::make ('', 403);
});
```

use the filter in routes.php:

```
	Route::group(array('before' => 'auth'), function() {
	
		Route::get('/', function()
		{
			return View::make('hello');
		});
	});
```
