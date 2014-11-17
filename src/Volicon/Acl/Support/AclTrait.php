<?php namespace Volicon\Acl\Support;

/**
 * Description of AclTrait
 *
 * @author nadav.v
 */
trait AclTrait {
	/**
	 *
	 * @param type $resource        	
	 * @param array $additional_values
	 *        	additional values from put/post...
	 * @return boolean
	 */
	public function check($resource = null, array $additional_values = []) {
		if (! $this->_guard) {
			return true;
		}
		
		if (in_array ( $resource, $this->allways_allow_resources )) {
			return true;
		}
		
		$filter_result = $this->filter ( $resource, $additional_values );
		
		return $filter_result !== FALSE;
	}
	
	public function filter($resource, array $ids = []) {
		
		$perm = $this->getPermission ( $resource );
		
		if ($perm->allowed && ! $perm->values) {
			return $ids;
		}
		
		if ($perm->allowed) {
			return array_intersect ( $ids, $perm->values );
		}
		
		if (!$perm->values) {
			return FALSE;
		}
		
		return array_diff ( $ids, $perm->values );
	}
}
