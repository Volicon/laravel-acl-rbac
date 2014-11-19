Volicon ACL (RBAC)
===========

ACL - Access Control List.
 More precisely this is an RBAC - Role Based Access Control - package for Laravel 4.

**Introduction:**

This RBAC plug-in defines permissions for users through roles or set/check permissions to routes.
Each role has an array of defined permissions.  
Each user is assigned 1 or more roles, the returned permission is a union of his assigned roles. 

Example of roles: admin, user, limited user, guest, etc.


1. [Installation](#installation)
2. [Configuration](#configuration)
3. [Hook callbacks](#hook)
4. [Routing](#routing)
5. [Api](#api)
6. [using with Models](#using-with-models)
7. [Examples](#examples)


##Installation

To install this package use Composer. 
Edit your project's composer.json file

```php
"require": {
		"volicon/laravel-acl": "dev-master",
},
"minimum-stability": "dev"
```

Next, update Composer from Terminal:

```php
  composer update
```

Once this operation is complete, add the package into your app with the service provider. 
Open app/config/app.php, edit providers array.

```php
'providers' => array(
	'Volicon\Acl\AclServiceProvider',
),
```

Recommended to add new alias into aliases array.

```php
'aliases' => array(
	'Acl'			  => 'Volicon\Acl\Facades\Acl',
	'AclUser'         => 'Volicon\Acl\AclUser',
	'AclRole'         => 'Volicon\Acl\AclRole',
)
```

Last step is to install ACL. You can easy do this by running artisan command:

```php
php artisan acl:install
```
this will create 4 new tables in your db.

##Configuration

**group_resources**:
you can put resources into a group for optimization.  
each resource in the group is referred to as a sub resource of the group.
when checking permission of a sub resource, the group's permission will be returned.

for the following example, any query of users.show or users.select will return the users.view permission.
```php
'group_resources' =>
	'users.view' => [  // group 
		'users.index',
		'users.show', 
		'User.select',
	],
	'role.view' => [   // another group
		'role.index',
		'role.show', 
		'Role.select',
	]
``` 


**allways_allow_resources**: 
any resource listed here will always return true without a database query for any logged in user.

```php 
	'allways_allow_resources' => [
		'local.viewCalendar'
	]
``` 

**cache**
its possible to use laravel's cache with Acl.
set the prefix key for caching.

```php
'using_cache' => true
'cache_key' => '_volicon_acl_'
```




**roleProviders**
Class which defines the permission behaviour for the role.
For example AdminRoleProvider will not allow to add permissions to role and will always return permission true without querying the db.
You can create a custom roleProviders which has a defined set of permissions or checks permission from a config file.
The default UsersRoleProvider will return the role as is.

The roleProviders Class controls add,remove and update of the role.  
For example:  Limit users in a role.  Security feature against: creating a new admin role, adding a user to admin, deleting admin role etc.

you may add a custom roleProviders through extending the \Volicon\Acl\RoleProviders\AclRoleProvider Class and overriding the methods: addRole, removeRole, updateRole, getPermission etc.

Role is assigned its roleProviders by role type.
UsersRoleProvider and AdminRoleProvider role types are provided as default.

```php
	'roleProviders' => [
		0 => '\Volicon\Acl\RoleProviders\UsersRoleProvider',
		1 => '\Volicon\Acl\RoleProviders\AdminRoleProvider'
	],
```


**Roles**: 
A model which holds a set of permissions.
The model behaviour and control is defined by the roleProviders Class.
The model holds an array of every user its assigned too.

```php
role format:
[ 'name' => 'role name',
  'permissions' => array of permissions, //(optional, default defined by 'default_permission', unless permission is set by 'type')
  'type' => 0,   // (optional default 0) - roleProviders id, more on this later
  'default' => 1 // (optional default 0), if => 1  can not remove this role.
]

permissions format: 
[ "resource" => "permission name", 
  "values" => [], // (optional), array of resources ids
  "allowed" => 1  // (optional, default set by 'default_permission' ) 
]
//	"allowed": 1 means **incdule all**, 0 means **exclude all**.
//	"allowed" => 0 -- allow action **except** for resource with id in array, if 'values' array is empty dont allow.
//	"allowed" => 1 -- allow action **only**   for resource with id in array, if 'values' array is empty allow action.
```
example:
Here we will define a role for a group admin who can create, and edit users except for user with id === 1, however he cannot delete any users.

```php
// Note: permission values may have been overridden in roleProviders.  
// 		 for example if 'type' => 1, then role provider admin would override resource permissions limitation.

'roles' => [ 
			 [ 'name' => 'GroupAdmin', 
	   		   'permissions' => [
					 ["resource" => "user.create", "values" => [] 	,"allowed" => 1 ],
				     ["resource" => "user.edit",   "values" => [1]  ,"allowed" => 0 ],
					 ["resource" => "user.delete", "values" => []   ,"allowed" => 0 ],
			    ],
			   'type' => 0,   // value of roleProviders: 0 - user, 1 - Admin ( or custom if changed or added another roleProviders ).
			   'default' => 0 // (optional) if ( 'default' => 1 ) can not delete role. 
			 ],
			 [
			  'name' => 'Admin',
			  'type' => 1,   // AdminRoleProvider
			  'default' => 1 // can not delete role. 
			 ]
		   ]
```

**default_permission:** 
boolean behaviour unknown resource and values. 
```php
'default_permission' => false
```


##Hook
Dynamically change permissions.
**registerHook** -- you can attach a callback to resource which will return a dynamically calculated permission.
callback function argument is Volicon\Acl\AclPermission.

```php
Acl::registerHook('media.list', function($permission){
	if(geoIp() !== 'Europe') {
		return new Volicon\Acl\AclPermission($permission->resource, [], false);  // dont allow
	}
	
	return $permission;  // else return defined role permission.
});

Acl::registerHook('ufo.list', function($permission){
	if(geoIp() === 'OuterSpace') {  
		$permission->allow = true; // manually set a permission.
		$permission->values[] = Auth::getUser()->user_id;
	return $permission;
});

```

##Routing:
(optional) check network requests:
you can set permissions for network request, same format as setting role permission.

in routes.php:
```php
	Route::group(array('before' => 'auth'), function() {	
		Route::resource('users', 'UsersController');
		Route::get('data', 'dataController@getData');
	});
```

Add filter in filters.php 
```php
Route::filter('auth', function()
{
	if (Auth::guest()) {
		return Redirect::to('/login.html');
	}
	try {
		\Volicon\Acl\AclRoute::validateRoute();
	} catch (\Volicon\Acl\Exceptions\NoPermissionsException $ex) {
		return Response::make (json_encode($ex->getMessage()), 403);
	}
});
```

##Api 
Boolean Acl::check($resource, array $ids = []); // [] is optional, ids of resource.
	//check() returns boolean, can perform the action of resource for all ids.
	//if check($resource) without [], returns true if $resource is not blocked.

Array Acl::filter($resource, array $ids = [])   // returns [] of authorized id from within the $ids [].
Volicon\Acl\AclPermission Acl::getPermission($resource) // returns permission of resource.  permission is an object with params: resource, values, and allow.

void Acl::registerHook($resource, $callback)
void Acl::reguard()   // check permissions with acl.
void Acl::unguard()  // remove acl security check
Boolean Acl::isGuard()
Volicon\Acl\RoleProviders\AclRoleProvider Acl::getRoleProvider($role_type)  // returns instants of 'role provider'
array Acl::getRoleProvidersTypes()  // array of ids
Illuminate\Support\Collection Acl::getRoles($roleIds = [])  // ($roleIds optional), returns all roles or roles for ids in array.
Volicon\Acl\AclUser Acl::getAuthUser()  // returns logged in user.
	
##AclUser
* @property int user_id
* @property int role_id
* @property array roles  // id of all roles assigned to this user.
* @property array user_types  
AclUser AclUser::find(id) // returns a user.
AclUser AclUser::findWithPermissions(id) // returns a user with an array of the users' permissions.
void $aclUser->setRoles(array $ids = []) // sets 1 or more role to the user, previous roles will be removed.

AclUser implements Acl method: check, filter, getPermission mentioned above.
when going through AclUser permission does not go through hooks, therefore the returned db result may vary from reality.

##AclRole
 * @property int $role_id
 * @property string $name
 * @property int $type
 * @property bool $default prevent deleting the role or change its name
 * @property Collection $permissions
 * @property Collection $users users ID's
 
**methods:**
* $acRole->getPermission
* $acRole->add() 	// add $acRole to databse.
* $acRole->update() // save $acRole to databse.
* $acRole->remove	// delete $acRole from databse.

#using with models
 Inheriting from Volicon\Acl\Models\AclModel will automatically check permissions.
AclModel listens to laravel events and checks permission for add, delete, and update.  For select event AclModel will add 'where' in query.



Add role through api:
#Examples:
```php
//Example 1:
$role = new AclRole();
$role->name = 'Users';
$role->permissions = [
	[
		'resource' => 'products.view',
		'values' => [],
		'allow' => true
	]
]
$role->users = [1,2];
$role->add(); 

//Example 2:
$role = new AclRole([
	'name' => 'product managers',
	'permissions' => [
		new AclPermission('products.manage',[],true)
	]
]);
$role->add();

$user = User::create(['username' => 'laravel', 'password' => 'strong']);
$aclUser = new AclUser($user);
$aclUser->setRoles(3);
```

AclModel example:

```php
use Volicon\Acl\Models\AclMode as Eloquent;

class Product extends Eloquent {
	protected $acl_field_key = 'product_id'; // if not defined use primary key
	
}

$products = Product::where('category', '=', 'good')->get(); //Acl add where depend of the permission of the resource Product.select
```
