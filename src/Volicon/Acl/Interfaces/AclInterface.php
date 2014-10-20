<?php namespace Volicon\Acl\Interfaces;

use Volicon\Acl\Models\Role;
use Volicon\Acl\RoleProviders\AclRoleProvider;

/**
 * Description of AclInterface
 *
 * @author nadav.v
 */
interface AclInterface {
	
	/**
	 *
	 * @param string $resource        	
	 * @param array $ids        	
	 * @return boolean
	 */
	public function check($resource, array $ids = []);
	public function filter($resource, array $ids = []);
	public function registerRoleProvider($role_type, AclRoleProvider $roleProvider);
	public function getPermission($resource);
	public function reguard();
	public function unguard();
	public function isGuard();
	public function getRoleProvider($role_type);
	public function getRoles($roleIds = []);
}
