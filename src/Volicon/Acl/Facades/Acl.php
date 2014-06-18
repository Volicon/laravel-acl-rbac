<?php namespace Volicon\Acl\Facades;

use Illuminate\Support\Facades\Facade;
use Volicon\Acl\AclResult;

/**
 * Description of Acl
 *
 * @author nadav.v
 */
class Acl extends Facade implements AclResult {
	
	protected static function getFacadeAccessor() { return 'Acl'; }
	
}
