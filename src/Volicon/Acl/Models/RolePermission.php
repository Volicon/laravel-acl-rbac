<?php namespace Volicon\Acl\Models;

class RolePermission extends \Eloquent {
	
	protected $table = 'role_permission';
	protected $fillable = ['role_id','permission_id','value','allowed'];
	protected $primaryKey = 'role_id';
	
	public function role() {
		return $this->HasOne('Volicon\Acl\Models\Role', 'role_id');
	}
}
