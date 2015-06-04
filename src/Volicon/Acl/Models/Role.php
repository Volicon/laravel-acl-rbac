<?php namespace Volicon\Acl\Models;

use Volicon\Acl\Models\GroupResources;
use Volicon\Acl\Support\MicrotimeDate;
use Illuminate\Database\Eloquent\Model as Eloquent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Exception;
use DB;
use Volicon\Acl\AclRole;

class Role extends Eloquent {
	protected $table = 'roles';
	protected $primaryKey = 'role_id';
	protected $fillable = [ 
			'name',
			'type',
			'permissions',
			'default'
	];
	protected $visible = [ 
			'role_id',
			'name',
			'type',
			'permissions',
			'default'
	];
	
	public static $cache_key;
	public static $use_cache = false;
	
	/**
	 *
	 * @return Roll
	 */
	public function users() {
		return $this->HasMany ( 'Volicon\Acl\Models\UserRole', 'role_id' );
	}
	
	public function permissions() {
		return $this->hasMany ( 'Volicon\Acl\Models\RolePermission', 'role_id' );
	}
	
	public static function getRoles(array $roleIds = [], $types = [], $resources = []) {
		
		if(self::$use_cache) {
			$roles = Cache::rememberForever(self::$cache_key, function() {
				$roles = static::with('users','permissions')->get();
				$result = new Collection();
			
				foreach($roles as $role) {
					$result[] = new AclRole($role);
				}
				$cache_prefix = Config::get('acl::cache_key', '_volicon_acl_');
				Cache::forever($cache_prefix.'_last_role_update', new MicrotimeDate());

				return $result;
			});
			/* @var $roles \Illuminate\Support\Collection */
			$need_filter = count($roles) || count($types) || count($resources);
			$roles = !$need_filter ? $roles : $roles->filter(
				function($role) use ($roleIds, $types, $resources) {
						return !(
								($roleIds && !in_array($role->role_id, $roleIds)) ||
								($types && !in_array($role->type, $types)) ||
								($resources && !array_intersect($role->permissions->lists('resource'), $resources))
						) ;

				}
			);

			return $roles;
		}
		
		$roles = static::with('users');
		
		$roles->with(['permissions' => function($query) use ($resources) {
			
			if(!$resources) {
				return;
			}
			
			$groupResources = GroupResources::getGroupResources();
			$resourcesIds = [];
			foreach($resources as $resource) {
				
				$resourcesIds[] = $groupResources->search($resource);;
			}
			
			$query->whereIn('permission_id', $resourcesIds);
			
		}]);
		
		if($types) {
			$roles->whereIn('type', $types);
		}
		
		if($roleIds) {
			$roles->whereIn('role_id', $roleIds);
		}
		
		$result = new Collection();
			
		foreach($roles->get() as $role) {
			$result[] = new AclRole($role);
		}

		return $result;
	}
	
	public static function addRole(AclRole $role) {
		$new_role = Role::create([
			'name'		=> $role->name,
			'type'		=> $role->type,
			'default'	=> $role->default
		]);
		
		$role->role_id = $new_role->role_id;
		
		RolePermission::updateRolePermissions($role);
		
		UserRole::updateRoleUsers($role);
		
		return $role->role_id;
	}
	
	public static function updateRole(AclRole $role) {
        DB::beginTransaction();
		$dbRole = static::find($role->role_id);
		
		if(!$dbRole) {
			throw new Exception("Role not found: ".$role->role_id);
		}
		
		if($dbRole->name !== $role->name && !$dbRole->default) {
			$dbRole->name = $role->name;
			$dbRole->save();
		}
		
		RolePermission::updateRolePermissions($role);
		
		UserRole::updateRoleUsers($role);
		DB::commit();
		return $role->role_id;
		
	}
	
	public static function removeRole($roleId) {
		$dbRole = static::find($roleId);
		
		if(!$dbRole) {
			throw new Exception("Role not found: ".$dbRole->role_id);
		}
		
		if($dbRole->default) {
			throw new NoPermissionsException("You cannot remove default role.");
		}
		
		$dbRole->permissions()->delete();
		$dbRole->users()->delete();
		$dbRole->delete();
		
		return $roleId;
	}
	
	public static function boot() {
		parent::boot();
		static::$cache_key = Config::get('acl::cache_key', '').'_model_Role_';
		static::$use_cache = Config::get('acl::using_cache', false);
		
		$clear_cache_func = function() {
			Cache::forget(Role::$cache_key);
		};
		
		Event::listen([
			'acl_role_added',
			'acl_role_updated',
			'acl_role_deleted',
		], $clear_cache_func);
		
		
	}
}
