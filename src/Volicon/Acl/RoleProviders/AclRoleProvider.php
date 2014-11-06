<?php namespace Volicon\Acl\RoleProviders;

use Volicon\Acl\Permission;
use Volicon\Acl\Models\Role;
use Volicon\Acl\Role as AclRole;
use Illuminate\Support\Collection;
use Exception;
use Config;

/**
 * Description of AclRoleProvider
 *
 * @author nadav.v
 */
abstract class AclRoleProvider {
	protected $role_type;
	protected $registersHandlers = [ ];
	
	final public function setRoleType($role_type) {
		if (! isset ( $this->role_type )) {
			$this->role_type = $role_type;
		} else {
			throw new Exception ( 'role type already set for ' . get_class ( new static () ) );
		}
	}
	
	public function getRoles(array $roleIds = [], $resources = []) {
		return Role::getRoles($roleIds, [$this->role_type], $resources);
	}
	
	public function addRole(AclRole $role) {
		$role->role_type = $this->role_type;
		$role->permissions = $this->addSubResources($role->permissions);
		return Role::addRole($role);
	}
	
	public function updateRole(AclRole $role) {
		$role->role_type = $this->role_type;
		$role->permissions = $this->addSubResources($role->permissions);
		return Role::updateRole($role);
	}

	public function removeRole($roleId) {
		return Role::removeRole($roleId);
	}
	
	public function getPermission($resource) {
		if (isset ( $this->registersHandlers [$resource] )) {
			return $this->registersHandlers [$resource] ();
		}
		
		$result = new Permission ( $resource );
		
		$roles = $this->getRoles([],[$resource]);
		
		foreach($roles as $role) {
			$result = $result->mergePermission($role->getPermission($resource));
		}
		
		return $result;
	}

	protected function addSubResources($permissions) {
		$result = $permissions->keyBy ( 'resource' );
		
		$sub_resources = [ ];
		$dependent_resources = [ ];
		$group_resources = Config::get('acl::group_resources', []);
		
		foreach ( $permissions as $permission ) {
			$resource = $permission ['resource'];
			if (! isset($group_resources[$resource])) {
				continue;
			}
			$config_permission_options = $group_resources[$resource];
			$permission_options = isset ( $config_permission_options ['@options'] ) ? $config_permission_options ['@options'] : [ ];
			
			if (! isset ( $permission_options ['depend'] )) {
				$permission_options ['depend'] = [ ];
			}
			if (! isset ( $permission_options ['sub_resource'] )) {
				$permission_options ['sub_resource'] = false;
			}
			
			if ($permission_options ['sub_resource']) {
				$sub_resources [] = $resource;
			} else if (count ( $permission_options ['depend'] )) {
				$dependent_resources = array_merge ( $dependent_resources, $permission_options ['depend'] );
			}
		}
		
		foreach ( $sub_resources as $resource ) {
			if (! in_array ( $resource, $dependent_resources ) && ! count ( $result [$resource] ['values'] )) {
				unset ( $result [$resource] );
			}
		}
		
		foreach ( $dependent_resources as $resource ) {
			if (! isset ( $result [$resource] )) {
				$result [$resource] = [ 
						'resource' => $resource,
						'values' => [ ],
						'allowed' => true 
				];
			}
		}
		
		return $result->values ()->toArray ();
	}

}
