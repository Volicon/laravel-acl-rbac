<?php namespace Volicon\Acl\Models;

class Role extends \Eloquent {
	protected $table = 'roles';
	protected $primaryKey = 'role_id';
	protected $fillable = ['name', 'admin'];
	protected $visible = ['role_id', 'name', 'admin'];

	/**
	 * 
	 * @return Roll
	 */
	public function users() {
		return $this->HasMany('Volicon\Acl\Models\UserRole', 'role_id');
	}
	
	public function permissions() {
		return $this->hasMany('Volicon\Acl\Models\RolePermission', 'role_id');
	}

		/**
	 * 
	 * @param int/string $role
	 * @return int
	 */
	public static function getRoleId($role) {
		$role_id = 0;
		
		$the_role = Role::where('name', '=', $role)->first(['role_id']);
		if($the_role) {
			$role_id = $the_role->role_id;
		}

		return $role_id;
		
	}
}
