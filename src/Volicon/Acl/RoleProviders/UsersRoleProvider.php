<?php namespace Volicon\Acl\RoleProviders;

use Volicon\Acl\AclPermission;
use Volicon\Acl\AclUser;
use Auth;

/**
 * Description of UsersRoleProvider
 *
 * @author nadav.v
 */
class UsersRoleProvider extends AclRoleProvider {
	
	public function getPermission($resource) {
		$result = new AclPermission($resource);
		
		$authUser = AclUser::find ( Auth::getUser ()->user_id );
		
		$roles = $this->getRoles($authUser->roles, [], [$resource]);
		
		foreach($roles as $role) {
			$permissions = $role->permissions->keyBy('resource');
			if(isset($permissions[$resource])) {
				$result = $result->mergePermission($permissions[$resource]);
			}
		}
		
		return $result;
		
	}
}
