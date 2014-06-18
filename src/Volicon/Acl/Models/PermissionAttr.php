<?php namespace Volicon\Acl\Models;

class PermissionAttr extends \Eloquent {
	
	protected $table = 'premission_attr';
	protected $primaryKey = 'permission_id';	
	
	public static function getResourceId($resource) {
		$resource = PermissionAttr::select()->where('resource', '=', $resource)->first();
		return $resource ? $resource->permission_id : NULL;
	}
}
