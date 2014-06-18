<?php namespace Volicon\Acl;

use Volicon\Acl\Models\RolePermission;

/**
 * Description of AclCheck
 *
 * @author nadav.v
 */
class AclCheck {
	
	
	const ONLY_TYPE_UNSET		= 0;
	const ONLY_TYPE_ALLOW		= 1;
	const ONLY_TYPE_DISSALOW	= 2;
	const ONLY_TYPE_FALSE		= 3;
	
	public static function checkForOnlyType(RolePermission $perm, $current_onlyType) {
		
		if($current_onlyType === AclCheck::ONLY_TYPE_UNSET) {
			$current_onlyType = $perm->allowed ? AclCheck::ONLY_TYPE_ALLOW : AclCheck::ONLY_TYPE_DISSALOW;
		}
		
		if(	$current_onlyType === AclCheck::ONLY_TYPE_FALSE ||
			$perm->value === null || 
			$perm->value === '' ||
			($perm->allowed && $current_onlyType === AclCheck::ONLY_TYPE_DISSALOW) ||
			(!$perm->allowed &&  $current_onlyType === AclCheck::ONLY_TYPE_ALLOW)
		) {
			return AclCheck::ONLY_TYPE_FALSE;
		}
		
		return $current_onlyType;
	}
}
