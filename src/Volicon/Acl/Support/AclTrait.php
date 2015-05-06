<?php namespace Volicon\Acl\Support;

/**
 * Description of AclTrait
 *
 * @author nadav.v
 */
trait AclTrait {
    
    protected $_guard = true;
    
	/**
	 *
	 * @param type $resource        	
	 * @param array $ids
	 *        	additional values from put/post...
	 * @return boolean
	 */
	public function check($resource = null, array $ids = []) {
		if (! $this->_guard) {
			return true;
		}
		
		if (in_array ( $resource, \Config::get ( 'acl::allways_allow_resources', [ ] ) )) {
			return true;
		}
		
		$perm = $this->getPermission ( $resource, $ids );
		
		if ($perm->allowed) {
			return true;
		}
		
		if (!$perm->values || $ids) {
			return FALSE;
		}
		
		return true;
	}
	
	public function filter($resource, array $ids = []) {
		
		$perm = $this->getPermission ( $resource, $ids );
		
		if ($perm->allowed && ! $perm->values) {
			return $ids;
		}
		
		if ($perm->allowed) {
			return array_intersect ( $ids, $perm->values );
		}
		
		if (!$perm->values) {
			return [];
		}
		
		return array_diff ( $ids, $perm->values );
	}
	
	public abstract function getPermission($resource = null, array $ids = []);
}
