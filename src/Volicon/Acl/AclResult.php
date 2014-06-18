<?php namespace Volicon\Acl;

/**
 * Description of AclResult
 *
 * @author nadav.v
 */
interface AclResult {
	
	const DISALLOW			= 0;
	const ALLOWED			= 1;
	const PARTLY_ALLOWED	= 2;
}
