<?php namespace Volicon\Acl\Support;

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
	public function getPermission($resource);
}
