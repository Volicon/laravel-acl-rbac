<?php namespace Volicon\Acl\Models;

class UserRole extends \Eloquent {
	protected $table = 'user_role';
	protected $fillable = ['user_id', 'role_id'];
	protected $primaryKey = 'role_id';
	
	public function role()
    {
        return $this->hasOne('Volicon\Acl\Models\Role', 'role_id');
    }
}
