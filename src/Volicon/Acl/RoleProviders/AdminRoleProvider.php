<?php namespace Volicon\Acl\RoleProviders;

use Volicon\Acl\Permission;
use Volicon\Acl\Models\Role;
use Volicon\Acl\Role as AclRole;
use Volicon\Acl\AclUser;
use Volicon\Acl\Exceptions\NoPermissionsException;
use Volicon\Acl\Facades\Acl;
use Volicon\Acl\Models\GroupResources;
use Illuminate\Support\Collection;
use Auth;

/**
 * Description of AdminRoleProvider
 *
 * @author nadav.v
 */
class AdminRoleProvider extends AclRoleProvider {
	
	public function getRoles(array $roleIds = [], $resources = []) {
		$roles = Role::getRoles($roleIds, [$this->role_type], false);
		
		$group_resources = GroupResources::getGroupResources();
		$permissions = new Collection();
		foreach($group_resources as $resource) {
			$permissions[] = $this->getPermission($resource);
		}
		
		foreach($roles as &$role) {
			$role->permissions = $permissions;
		}
		
		return $roles;
	}
	
	public function addRole(AclRole $role) {
		
		if(Acl::isGuard()) {
			$authUser = AclUser::find ( Auth::getUser ()->user_id );
			if (! in_array ( $this->role_type, $authUser->user_types )) {
				return new NoPermissionsException ( 'Only admin user can add admin roles' );
			}
		}
		
		$role->permissions = [];
		
		return parent::addRole ( $role );
	}
	public function updateRole(AclRole $role) {
		if(Acl::isGuard()) {
			$authUser = AclUser::find ( Auth::getUser ()->user_id );
			if (! in_array ( $this->role_type, $authUser->user_types )) {
				return new NoPermissionsException ( 'Only admin user can update admin roles' );
			}
		}
		
		$role->permissions = [];
		
		return parent::updateRole ( $role );
	}
	public function removeRole($roleId) {
		if(Acl::isGuard()) {
			$authUser = AclUser::find ( Auth::getUser ()->user_id );
			if (! in_array ( $this->role_type, $authUser->user_types )) {
				return new NoPermissionsException ( 'Only admin user can remove admin roles' );
			}
		}
		
		return parent::removeRole ( $roleId );
	}
	public function getPermission($resource) {
		return new Permission ( $resource, [ ], true );
	}
	public function registerResourceHandler($resource, $callback) {
		return;
	}
}
