<?php namespace Volicon\Acl\Models;

use Volicon\Acl\Models\GroupResources;
use Illuminate\Support\Collection;

use Volicon\Acl\Role as AclRole;

class Role extends \Eloquent {
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
		
		$roles = static::with('users');
		
		$roles->with(['permissions' => function($query) use ($resources) {
			
			if(!$resources) {
				return;
			}
			
			$groupResources = GroupResources::getGroupResources();
			$resourcesIds = [];
			foreach($resources as $resource) {
				$resourcesIds[] = array_search($resource, $groupResources);
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
		
		$dbRole->permissions()->forceDelete();
		$dbRole->users()->forceDelete();
		
		//TODO: bug in laravel, all roles are deleted and not only 
		//$dbRole->forceDelete();
		static::where('role_id', '=', $dbRole->role_id)->delete();
		
		return $roleId;
	}
}
