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
	
	public function getPermission($resource, array $ids = []) {
		$result = new AclPermission($resource);
		
		if($ids) {
			$result = $result->newSubPermission($ids);
		}
		
		$authUser = AclUser::find ( Auth::id() );
		
		$roles = $this->getRoles($authUser->roles, [], [$resource]);
		
		foreach($roles as $role) {
			$permissions = $role->permissions->keyBy('resource');
			if(isset($permissions[$resource])) {
				$result = $result->mergePermission($permissions[$resource]->newSubPermission($ids));
			}
		}
		
		return $result;
		
	}
}
